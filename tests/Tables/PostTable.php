<?php

namespace timolake\LivewireTables\Tests\Tables;

use Illuminate\Database\Eloquent\Builder;
use timolake\LivewireTables\LivewireModelTable;
use timolake\LivewireTables\Tests\Models\Post;

class PostTable extends LivewireModelTable
{
    public bool $paginate = false;
    public bool $hasSearch= true;

    public array $fields = [
        [
            'title' => 'title',
            'name' => 'title',
            'header_class' => '',
            'cell_class' => '',
            'sortable' => true,
            'searchable' => true,
        ],
        [
            'title' => 'user',
            'name' => 'user.name',
            'header_class' => '',
            'cell_class' => '',
            'sortable' => true,
            'searchable' => true,
        ],
        [
            'title' => 'color',
            'name' => 'user.color.name',
            'header_class' => '',
            'cell_class' => '',
            'sortable' => true,
            'searchable' => true,
        ],
        [
            'title' => 'comments',
            'name' => 'comments.body',
            'header_class' => '',
            'cell_class' => '',
            'sortable' => true,
            'searchable' => true,
        ],
        [
            'title' => 'tags',
            'name' => 'tags.name',
            'header_class' => '',
            'cell_class' => '',
            'sortable' => true,
            'searchable' => true,
        ],

    ];

    function model()
    {
        return Post::class;
    }



    public function render()
    {
        return view('post-table',["rowData" => $this->query()]);
    }

    protected function updateQuery(Builder &$query)
    {
        $query->with(['user.color','comments','tags']);
    }


}