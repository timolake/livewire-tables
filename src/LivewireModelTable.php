<?php

namespace timolake\LivewireTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class LivewireModelTable extends Component
{

    public $fields = [];
    public $css;

    public $sortField = null;
    public $sortDir = null;

    public $paginate = true;
    public $pagination = 10;
    public $totalRows = null;
    public $paginationItems = [];
    public $selectAllRows = false;

    public $hasSearch = true;
    public $search = null;
    public $previousSearch = null;

    public $hasTrashed = false;
    public $trashed;

    public $checkAll = false;
    public $HasCheckboxes = false;
    public $checkedItems = [];


    protected $listeners = ['sortColumn' => 'setSort'];

    public function mount(Request $request)
    {
        $this->trashed = $request->trashed == 1 ?? false;
        $this->search = $request->search ?? null;
        $this->sortField = $request->sortField ?? null;
        $this->sortDir = $request->sortDir ?? null;
        if(isset($request->paginationPage)){
            $this->setPage($request->paginationPage);

        }
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
            $this->totalRows = $query->count();
            $this->buildPaginationItems();
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
                ->orderBy($field),
            $this->sortDir
        );
    }

    protected function search($query, $queryFields)
    {
        $searchFields = $queryFields->where('searchable', true)->pluck('name')->toArray();

        foreach (explode(" ", $this->search) as $key => $searchString) {
            $this->whereLike($query, $searchFields, $searchString);
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
                        [$relationshipName, $field] = explode('.', $attribute);
                        [$parentTable, $parentId, $subTable, $subId] = $this->getRelationshipKeys($this->getRelationship($relationshipName));

                        $query->orWherein($parentId, function ($query) use ($field, $subTable, $subId, $searchTerm) {
                            $query->select($subId)
                                ->from($subTable)
                                ->where($field, 'LIKE', "%{$searchTerm}%");
                        });
                    },
                    function (Builder $query) use ($attribute, $searchTerm) {
                        $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                    }
                );
            }
        });
    }

    public function getRelationship(string $name): Relation
    {
        return app($this->model())->$name();
    }

    public function getRelationshipKeys(Relation $relationship): array
    {
        $parentTable = null;
        $parentId = null;
        $subTable = null;
        $subId = null;

        $fullForeignKey = $relationship->getQualifiedForeignKeyName();

        if ($relationship instanceof BelongsTo) {
            [$parentTable, $parentId] = explode(".", $fullForeignKey);

            $fullOwnerKey = $relationship->getQualifiedOwnerKeyName();
            [$subTable, $subId] = explode(".", $fullOwnerKey);
        }

        if ($relationship instanceof HasMany) {
            [$subTable, $subId] = explode(".", $fullForeignKey);

            $parentId = $relationship->getLocalKeyName();
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

    public function buildPaginationItems()
    {
        $maxCount = $this->totalRows;
        $this->paginationItems = [];
        $options = config('livewire-tables.pagination_items', [10, 25, 50, 100]);
        $paginationNotInOptions = array_search($this->pagination, $options) === false;

        foreach ($options as $option) {
            if ($option < $maxCount) {
                $this->paginationItems[$option] = $option;

                if ($paginationNotInOptions
                    and $this->pagination < $option
                ) {
                    $this->pagination = $option;
                    $paginationNotInOptions = false;
                }
            } else {
                break;
            }
        }
        $this->paginationItems[$maxCount] = "$maxCount";

        if ($this->selectAllRows
            or $this->pagination > $maxCount
            or array_search($this->pagination, $options) === false
        ) {
            $this->pagination = Arr::last($this->paginationItems);
            $this->selectAllRows = false;
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
        if ($this->HasCheckboxes) {
            $this->resetCheckboxes();
        }
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
            if (Str::contains($selectField['name'], '.')) {
                $fieldParts = explode('.', $selectField['name']);
                $selectField['name'] = $fieldParts[0] . '.' . $fieldParts[1];
            }

            return $selectField;
        });
    }

    public function toggleCheckAll()
    {
        $allIds = $this->query()->pluck("id")->toArray();

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

    public function paginationChanged()
    {
        $this->resetCheckboxes();
    }

}
