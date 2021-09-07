<?php

namespace timolake\LivewireTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;

class LivewireModelTable extends Component
{
    public $sortField = null;
    public $sortDir = null;
    public $search = null;
    public $paginate = true;
    public $pagination = 10;
    public $hasSearch = true;
    public $fields = [];
    public $css;
    public $hasTrashed = false;
    public $trashed = false;

    protected $listeners = ['sortColumn' => 'setSort'];

    public function setSort($column)
    {
        $this->sortField = array_key_exists('sort_field', $this->fields[$column]) ? $this->fields[$column]['sort_field'] : $this->fields[$column]['name'];
        if (! $this->sortDir) {
            $this->sortDir = 'asc';
        } elseif ($this->sortDir == 'asc') {
            $this->sortDir = 'desc';
        } else {
            $this->sortDir = null;
            $this->sortField = null;
        }
    }

    protected function query()
    {
        return $this->paginate($this->buildQuery());
    }

    protected function querySql()
    {
        return $this->buildQuery()->toSql();
    }

    protected function buildQuery()
    {
        $model = app($this->model());
        $query = $model->newQuery();
        $queryFields = $this->generateQueryFields($model);

        if ($this->with()) {
            $query = $query->with($this->with());
            if ($this->sortIsRelatedField()) {
                $query = $this->sortByRelatedField($query, $model);
            } else {
                $query = $this->sort($query);
            }
        } else {
            $query = $this->sort($query);
        }

        if ($this->hasSearch && $this->search && $this->search !== '') {
            $query = $this->search($query, $queryFields);
        }

        if($this->trashed){
            $query->onlyTrashed();
        }
        //		dd($query->toSql());
        return $query;
    }

    protected function sort($query)
    {
        if (! $this->sortField || ! $this->sortDir) {
            return $query;
        }

        return $query->orderBy($this->sortField, $this->sortDir);
    }

    protected function search($query, $queryFields)
    {
        $searchFields = $queryFields->where('searchable', true)->pluck('name')->toArray();

        foreach ( explode(" ", $this->search) as $key => $searchString)
            foreach ($searchFields as $searchfield)
                $this->whereLike($query, $searchFields, $searchString);

        return $query;
    }

    protected function whereLike(Builder &$query, array $attributes, string $searchTerm){

        $query->where(function (Builder $query) use ($attributes, $searchTerm) {
            foreach (array_wrap($attributes) as $attribute) {
                $query->when(
                    str_contains($attribute, '.'),
                    function (Builder $query) use ($attribute, $searchTerm) {
                        [$relationName, $relationAttribute] = explode('.', $attribute);

                        $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                            $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                        });
                    },
                    function (Builder $query) use ($attribute, $searchTerm) {
                        $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                    }
                );
            }
        });
    }

    protected function paginate($query)
    {
        if (! $this->paginate) {
            return $query->get();
        }

        return $query->paginate($this->pagination ?? 15);
    }

    protected function sortByRelatedField($query, $model)
    {
        $relations = collect(explode('.', $this->sortField));
        $relationship = $relations->first();
        $sortField = $relations->pop();

        return $query->orderBy($model->{$relationship}()->getRelated()->getTable().'.'.$sortField, $this->sortDir);
    }

    public function model()
    {
    }

    protected function with()
    {
        return [];
    }

    public function clearSearch()
    {
        $this->search = null;
    }

    /**
     * @return bool
     */
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
                $selectField['name'] = $fieldParts[0].'.'.$fieldParts[1];
            }

            return $selectField;
        });
    }
}
