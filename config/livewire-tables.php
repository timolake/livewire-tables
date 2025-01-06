<?php

return [


    //----------------------------------------------------
    // Class Namespace
    //----------------------------------------------------
    // This value sets the root namespace for Livewire table component classes in
    // your application. This value effects any livewire-tables file helper commands,
    // like `artisan livewire-tables:make`
    'class_namespace' => 'App\\Http\\Livewire\\Tables',

    //----------------------------------------------------
    // View Path
    //----------------------------------------------------
    // This value sets the path for Livewire table component views. This effects
    // File manipulation helper commands like `artisan livewire-tables:make`

    'view_path' => resource_path('views/livewire/tables'),

    //----------------------------------------------------
    // Default CSS configuration
    //----------------------------------------------------
    // Use these values to set default CSS classes for the corresponding elements.

    'css' => [
        'wrapper' => "null",
        'table' => "table",
        'thead' => "table__head",
        'th' => "table__cell table__cell--head",
        'tbody' => "table__body",
        'tr' => "table__row",
        'td' => "table__cell",
        'search_wrapper' => null,
        'search_input' => null,
        'sorted_asc' => null,
        'sorted_desc' => null,
        'pagination_wrapper' => null,
    ],

    //----------------------------------------------------
    // PaginationItems
    //----------------------------------------------------
    'pagination_items' => [10 => 10, 25 => 25, 50 => 50, 100 => 100]
];
