@foreach($fields as $key => $field)
    @if(!isset($field['visible_in_header']) or $field['visible_in_header'] == true)
        <th class="table__cell table__cell--head" wire:click="$emit('sortColumn',{{$key}})">
            <div class="flex items-center space-x-1">
                <div style="cursor: pointer">
                    @lang($field["title"])
                </div>
                <div>
                    @if($sortField == $field["name"])
                        @if($sortDir == "asc")
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24">
                                <path d="M6 21l6-8h-4v-10h-4v10h-4l6 8zm16-4h-8v-2h8v2zm2 2h-10v2h10v-2zm-4-8h-6v2h6v-2zm-2-4h-4v2h4v-2zm-2-4h-2v2h2v-2z"/>
                            </svg>
                        @elseif($sortDir == "desc")
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24">
                                <path d="M6 21l6-8h-4v-10h-4v10h-4l6 8zm16-12h-8v-2h8v2zm2-6h-10v2h10v-2zm-4 8h-6v2h6v-2zm-2 4h-4v2h4v-2zm-2 4h-2v2h2v-2z"/>
                            </svg>
                        @endif
                    @endif
                </div>
            </div>

        </th>
    @endif

@endforeach