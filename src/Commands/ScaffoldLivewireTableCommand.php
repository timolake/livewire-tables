<?php

namespace timolake\LivewireTables\Commands;

use Illuminate\Support\Facades\File;
use Livewire\Commands\ComponentParser;
use Livewire\Commands\FileManipulationCommand;
use ReflectionClass;

class ScaffoldLivewireTableCommand extends FileManipulationCommand
{
    protected $signature = 'livewire-tables:scaffold {name}';

    protected $description = 'Scaffold a Livewire Table component.';

    public function handle()
    {
        $this->parser = new ComponentParser(config('livewire-tables.class_namespace', 'App\\Http\\Livewire\\Tables'),
            config('livewire-tables.view_path', resource_path('views/livewire/tables')), $this->argument('name'));
        $view = $this->createView();
        $view && $this->line('<options=bold,reverse;fg=green> Table Scaffolded </> ');
        $view && $this->line("<options=bold;fg=green>VIEW:</>  {$this->parser->relativeViewPath()}");
    }

    protected function createView()
    {
        $viewPath = $this->parser->viewPath();
        $this->ensureDirectoryExists($viewPath);
        File::put($viewPath, $this->viewContents());

        return $viewPath;
    }

    protected function viewContents()
    {
        $template = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'table.stub');

        // We need access to the table component class to retrieve and construct the fields and css
        $tableComponent = new ReflectionClass($this->parser->classNamespace().'\\'.$this->parser->className());
        [$fields, $css] = $this->constructFieldsAndCss($tableComponent);
        [$patterns, $replacements] = $this->constructCssPatternsAndReplacements($css);

        // Replace the contents of the sub with header and data rows and css classes
        return preg_replace('/\[header\]/', $this->headerRows($fields, $css),
            preg_replace('/\[data\]/', $this->dataRows($fields, $css),
                str_replace($patterns->toArray(), $replacements->toArray(), $template)));
    }

    protected function headerRows(array $fields, array $css)
    {

        return '';
//        $cells = [];
//        foreach ($fields as $key => $field) {
//            $cell = "\t\t\t<th".$this->getHeaderClass($field, $css).$this->getSortableAction($key,
//                    $field).'>'.$field['title'].'</th>';
//            if (end($fields) !== $field) {
//                $cell .= PHP_EOL;
//            }
//            array_push($cells, $cell);
//        }
//
//        return implode('', $cells);
    }

    protected function dataRows(array $fields, array $css)
    {
        $cells = [];
        foreach ($fields as $key => $field) {
            $cell = "\t\t\t\t<td".$this->getDataClass($field, $css).'>{{ $row->'.str_replace('.', '->',
                    $field['name']).' }}</td>';
            if (end($fields) !== $field) {
                $cell .= PHP_EOL;
            }
            array_push($cells, $cell);
        }

        return implode('', $cells);
    }

    protected function getDataClass(array $field, array $css)
    {
        if ((isset($css['td']) && $css['td'] && $css['td'] !== '') || (isset($field['cell_class']) && $field['cell_class'] && $field['cell_class'] !== '')) {
            if ((isset($css['td']) && $css['td'] && $css['td'] !== '') && (isset($field['cell_class']) && $field['cell_class'] && $field['cell_class'] !== '')) {
                return ' class="'.$css['td'].' '.$field['cell_class'].'"';
            } elseif (isset($css['td']) && $css['td'] && $css['td'] !== '') {
                return ' class="'.$css['td'].'"';
            } else {
                return ' class="'.$field['cell_class'].'"';
            }
        }
    }

    protected function getHeaderClass(array $field, array $css)
    {
        if ((isset($css['th']) && $css['th'] && $css['th'] !== '') || (isset($field['header_class']) && $field['header_class'] && $field['header_class'] !== '')) {
            if ((isset($css['th']) && $css['th'] && $css['th'] !== '') && (isset($field['header_class']) && $field['header_class'] && $field['header_class'] !== '')) {
                return ' class="'.$css['th'].' '.$field['header_class'].'"';
            } elseif (isset($css['th']) && $css['th'] && $css['th'] !== '') {
                return ' class="'.$css['th'].'"';
            } else {
                return ' class="'.$field['header_class'].'"';
            }
        }
    }

    protected function getSortableAction($key, array $field)
    {
        if (isset($field['sortable']) && $field['sortable']) {
            return ' wire:click="$emit(\'sortColumn\', '.$key.')"';
        }
    }

    /**
     * @param $css
     * @return array
     */
    protected function constructCssPatternsAndReplacements($css): array
    {
        $patterns = collect(array_keys($css));
        $patterns->each(function ($pattern, $key) use ($patterns) {
            $patterns[$key] = '['.$pattern.']';
        });
        $replacements = collect(array_values($css));
        $replacements->each(function ($replacement, $key) use ($replacements) {
            ! is_null($replacement) ? $replacements[$key] = ' class="'.$replacement.'"' : $replacements[$key] = '';
        });

        return [$patterns, $replacements];
    }

    /**
     * @param  \ReflectionClass  $tableComponent
     * @return array
     */
    protected function constructFieldsAndCss(ReflectionClass $tableComponent): array
    {
        $properties = $tableComponent->getDefaultProperties();
        $fields = $properties['fields'];
        $css = $this->setCssArray($properties['css']);

        return [$fields, $css];
    }

    protected function setCssArray($css): array
    {
        if (isset($css) && $css !== null) {
            return array_merge(config('livewire-tables.css'), $css);
        } else {
            return config('livewire-tables.css', []);
        }
    }
}
