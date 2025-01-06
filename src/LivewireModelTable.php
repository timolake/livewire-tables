<?php

namespace timolake\LivewireTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

abstract class LivewireModelTable extends Component
{
    use WithPagination;
    public ?string $sessionId = null;
    public array $fields = [];
    public ?string $css;

    public ?string $sortField = null;
    public ?string $sortDir = null;
    public bool $saveSortInSession = true;


    public bool $paginate = true;
    public int $pagination = 10;
    public array $paginationItems = [];

    public bool $hasSearch = true;
    public ?string $search = null;
    public ?string $previousSearch = null;
    public bool $search_exploded_string = true;

    public bool $hasTrashed = false;
    public bool $trashed = false;

    public bool $checkAll = false;
    public bool$HasCheckboxes = false;
    public array $checkedItems = [];

    public string $idField = 'id';

    protected $listeners = ['sortColumn' => 'setSort'];

    public bool $addMaxCountToPaginate = false;

    public function mount(Request $request)
    {

        $this->modelClass = $this->model();
        $tempModel = (new ($this->modelClass));
        $this->idField = $tempModel->getKeyName();

        $className = str_slug(get_class($this));
        $this->sessionId = $request->has("sessionId")
            ? $className.$request->sessionId
            :$className;

        $this->initAttributesFromSession();

    }

    public function setSort($column)
    {
        $field = $this->fields[$column];

        if (isset($field["sortable"]) and $field["sortable"]) {
            $sortField = array_key_exists('sort_field', $field)
                ? $field['sort_field']
                : $field['name'];

            if ($sortField != $this->sortField) {
                $this->sortField = $sortField;
                $this->sortDir = 'asc';
            } else {
                $this->sortDir = $this->sortDir == "asc" ? "desc" : "asc";
            }

            if($this->saveSortInSession){
                $this->PutTableSession("sortField",$this->sortField);
                $this->PutTableSession("sortDir",$this->sortDir);
            }
        }
    }

    protected function query()
    {
        return $this->paginate($this->buildQuery());
    }

    protected function buildQuery()
    {
        $model = app($this->model());
        $query = $model->newQuery();
        $queryFields = $this->generateQueryFields($model);

        if ($this->sortIsRelatedField()) {
            $query = $this->sortByRelatedField($query, $model);
        } else {
            $query = $this->sort($query);
        }
        if ($this->hasSearch && $this->search && $this->search !== '') {

            $query = $this->search($query, $queryFields);

            if ($this->search != $this->previousSearch) {
                $this->resetCheckboxes();
            }

            $this->previousSearch = $this->search;
        }

        if ($this->trashed) {
            $query->onlyTrashed();
        }

        $this->updateQuery($query);

        if ($this->paginate) {
            $this->buildPaginationItems($query);
        }
        return $query;
    }

    protected function updateQuery(Builder &$query)
    {
    }

    protected function sort($query)
    {
        if (!$this->sortField || !$this->sortDir) {
            return $query;
        }

        return $query->orderBy($this->sortField, $this->sortDir);
    }

    protected function sortByRelatedField($query, $model)
    {
        [$relationshipName, $field] = explode('.', $this->sortField);
        $relationship = $this->getRelationship($relationshipName);
        [$parentTable, $parentId, $subTable, $subId] = $this->getRelationshipKeys($relationship);

        return $query->orderBy(
            $relationship->getModel()::select("$field")
                ->whereColumn("$subTable.$subId", "$parentTable.$parentId")
                ->orderBy($field)
                ->limit(1),
            $this->sortDir
        );
    }

    protected function search($query, $queryFields)
    {
        $searchFields = $queryFields->where('searchable', true)->pluck('name')->toArray();

        if($this->search_exploded_string){
            foreach (explode(" ", $this->search) as $key => $searchString) {
                $this->whereLike($query, $searchFields, $searchString);
            }
        }else{
            $this->whereLike($query, $searchFields, $this->search);
        }

        return $query;
    }

    protected function whereLike(Builder &$query, $attributes, string $searchTerm)
    {
        $query->where(function (Builder $query) use ($attributes, $searchTerm) {
            foreach (array_wrap($attributes) as $attribute) {
                $query->when(
                    str_contains($attribute, '.'),
                    function (Builder $query) use ($attribute, $searchTerm) {
                        $this->searchInRelationship($query, $attribute, $searchTerm);
                    },
                    function (Builder $query) use ($attribute, $searchTerm) {
                        $query->orWhere(DB::raw('lower('.$attribute.')'), 'LIKE', Str::lower("%{$searchTerm}%"));
                    }
                );
            }
        });
    }

    public function searchInRelationship(Builder &$query, $attribute, string $searchTerm)
    {
        [$relationshipName, $field] = $this->parseAtribute($attribute);

        $relationship = $this->getRelationship($relationshipName);
        [$parentTable, $parentId, $subTable, $subId] = $this->getRelationshipKeys($relationship);

        if($relationship instanceof BelongsToMany) {
            $query->orWhereHas($relationshipName, function (Builder $query) use ($field,$parentTable, $parentId, $subTable, $subId, $searchTerm) {
                $query->where($field, "like","%{$searchTerm}%");
            });
        }else{
            $query->orWherein($parentId, function ($query) use ($field, $subTable, $subId, $searchTerm, $relationship,$relationshipName) {
                if (str_contains($field, '.')) {
                    //----------------------------------------------------
                    // search in nested relationship
                    // ex: user->post->comments
                    //----------------------------------------------------
                    [$fieldRelationshipName, $fieldField] = $this->parseAtribute($field);
                    [$fieldParentTable, $fieldParentId, $fieldSubTable, $fieldSubId] = $this->getRelationshipKeys($this->getRelationship($fieldRelationshipName, $relationshipName));

                    $query
                        ->select($subId)
                        ->from($subTable)
                        ->whereIn(
                            DB::raw('lower('.$fieldParentId.')'),
                            function ($query) use ($fieldParentId, $fieldSubTable, $fieldSubId, $fieldField, $searchTerm) {
                                $query
                                    ->select($fieldSubId)
                                    ->from($fieldSubTable)
                                    ->where(DB::raw('lower('.$fieldField.')'), 'LIKE', Str::lower("%{$searchTerm}%"));
                            }
                        );
                } else {

                    //----------------------------------------------------
                    // search in subtable
                    // ex: user->posts
                    //----------------------------------------------------
                    $query->select($subId)
                        ->from($subTable)
                        ->where(DB::raw('lower('.$field.')'), 'LIKE', Str::lower("%{$searchTerm}%"));

                }

            });
        }
    }

    public function parseAtribute(string $attribute)
    {
        //----------------------------------------------------
        // explode string
        //----------------------------------------------------
        $items = explode('.', $attribute);
        $relationshipName = $items[0];

        //----------------------------------------------------
        // check for nested relationship
        //----------------------------------------------------
        if (count($items) > 1) {
            Arr::forget($items, 0);
            $items = array_values($items);
        }

        //----------------------------------------------------
        // set field value with . notation
        //----------------------------------------------------
        $field = count($items) > 1
            ? implode(".", $items)
            : $items[0];

        return [$relationshipName, $field];
    }

    public function getRelationship(string $name, ?string $parent = null): Relation
    {
        if (isset($parent)) {
            $parentRelation = app($this->model())->$parent();
            return $parentRelation->getRelated()->$name();
        }
        return app($this->model())->$name();
    }

    public function getRelationshipKeys(Relation $relationship): array
    {
        $parentTable = null;
        $parentId = null;
        $subTable = null;
        $subId = null;


        if ($relationship instanceof HasOne) {
            [$parentTable, $parentId] = explode(".", $relationship->getQualifiedParentKeyName());
            [$subTable, $subId] = explode(".",  $relationship->getQualifiedForeignKeyName());
        }

        if ($relationship instanceof BelongsTo) {
            [$parentTable, $parentId] = explode(".", $relationship->getQualifiedForeignKeyName());
            [$subTable, $subId] = explode(".",  $relationship->getQualifiedOwnerKeyName());
        }

        if ($relationship instanceof HasMany) {
            [$parentTable, $parentId] = explode(".", $relationship->getQualifiedParentKeyName());
            [$subTable, $subId] = explode(".",  $relationship->getQualifiedForeignKeyName());
        }

        if ($relationship instanceof BelongsToMany) {
            [$parentTable, $parentId] = explode(".", $relationship->getQualifiedParentKeyName());
            [$subTable, $subId] = explode(".",  $relationship->getQualifiedRelatedKeyName());
        }

        return [$parentTable, $parentId, $subTable, $subId];
    }

    protected function paginate($query)
    {
        if (!$this->paginate) {
            return $query->get();
        }

        return $query->paginate($this->pagination ?? 15);
    }

    public function buildPaginationItems($query)
    {
        $this->paginationItems= config('livewire-tables.pagination_items');

        if($this->addMaxCountToPaginate){

            $this-> paginationItems= [];
            $maxCount = $query->count();

            foreach (config('livewire-tables.pagination_items') as $option) {
                if ($option < $maxCount) {
                    $this->paginationItems[$option] = $option;
                } else {
                    break;
                }
            }

            $this->paginationItems[$maxCount] = "$maxCount";

        }
    }

    abstract function model();

    protected function with()
    {
        return [];
    }

    public function clearSearch()
    {
        $this->search = null;
        $this->forgetTableSession("search");

        if ($this->HasCheckboxes) {
            $this->resetCheckboxes();
        }

        $this->resetPage();
    }

    protected function sortIsRelatedField(): bool
    {
        return $this->sortField && Str::contains($this->sortField, '.') && $this->sortDir;
    }

    protected function setSelectFields($query, $queryFields)
    {
        return $query->select($queryFields->pluck('name')->toArray());
    }

    protected function generateQueryFields($model)
    {
        return (collect($this->fields))->transform(function ($selectField) use ($model) {
//            if (Str::contains($selectField['name'], '.')) {
//                $fieldParts = explode('.', $selectField['name']);
//                $selectField['name'] = $fieldParts[0] . '.' . $fieldParts[1];
//            }

            return $selectField;
        });
    }

    public function toggleCheckAll()
    {
        $allIds = $this->query()->pluck($this->idField)->toArray();

        if (empty($this->checkedItems)) {
            $this->checkedItems = $allIds;
        } else {
            if (count($this->checkedItems) == count($allIds)) {
                $this->checkedItems = [];
            } else {
                $this->checkedItems = $allIds;
                $this->checkAll = true;
            }
        }
    }

    public function resetCheckboxes()
    {
        $this->checkAll = false;
        $this->checkedItems = [];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    //----------------------------------------------------
    // search, sort, pagination & trashed in session
    //----------------------------------------------------
    public function updatedSearch()
    {
        $this->PutTableSession("search",$this->search);

    }
    public function updatedTrashed()
    {
        $this->PutTableSession("trashed",$this->trashed);

    }
    public function updatedSortField()
    {
        if($this->saveSortInSession) {
            $this->PutTableSession("sortfield", $this->sortfield);
        }
    }
    public function updatedSortDir()
    {
        if($this->saveSortInSession) {
            $this->PutTableSession("sortDir", $this->sortDir);
        }
    }
    public function updatedPagination()
    {
        $this->resetCheckboxes();
        $this->PutTableSession("pagination",$this->pagination);
    }
    public function updatedPaginators ()
    {
        $this->PutTableSession("paginators",$this->paginators);
    }

    public function initAttributesFromSession()
    {

        if($this->hasTableSession("search")) {
            $this->search = $this->getTableSession("search");
        }

        if($this->hasTableSession("sortField")){
            $this->sortField = $this->getTableSession("sortField");
        }

        if($this->hasTableSession("sortDir")) {
            $this->sortDir = $this->getTableSession("sortDir");
        }

        if($this->hasTableSession("trashed")){
            $this->trashed = $this->getTableSession("trashed");
        }

        if($this->hasTableSession("pagination")){
            $this->pagination = $this->getTableSession("pagination");
        }

        if ($this->hasTableSession("paginators")){
            $this->paginators = $this->getTableSession("paginators");
        }
    }
    public function resetTableSession()
    {
        $this->clearSearch();

        //----------------------------------------------------
        // clear session
        //----------------------------------------------------
        $this->forgetTableSession("sortField");
        $this->forgetTableSession("sortDir");
        $this->forgetTableSession("trashed");
        $this->forgetTableSession("pagination");
        $this->forgetTableSession("paginators");

        //----------------------------------------------------
        // clear form values
        //----------------------------------------------------
        $this->sortField = null;
        $this->sortDir = null;
        $this->trashed = false;
        $this->pagination = 10;
        //paginators reset by clearSearch

    }

    //----------------------------------------------------
    // php session with session id
    //----------------------------------------------------
    public function getTableSession($key)
    {
        return Session::get($this->sessionId.".$key");
    }

    public function PutTableSession($key, $value)
    {

        Session::put($this->sessionId.".$key", $value);
    }

    public function HasTableSession($key)
    {
        return Session::has($this->sessionId.".$key");
    }

    public function forgetTableSession($key)
    {
        Session::forget($this->sessionId.".$key");
    }

}
