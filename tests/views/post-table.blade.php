<div class="null">


    <div wire:loading class="overlay">
        <div class="d-flex justify-content-center align-items-center  h-100">
            <div class="spinner-border text-primary" style="height: 5em; width: 5em" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="d-flex justify-content-between pb-3">

                <div class="" style="display:flex; background-color: red">
                    {{--                    @if ($paginate)--}}
                    {{--                        <x-select :items="$paginationItems" wire:model="pagination" wire:change="paginationChanged"></x-select>--}}

                    {{--                    @endif--}}
                </div>
                <div class="d-flex align-items-center align-content-center gap-3">

                    @if ($hasSearch)
                        <div class="d-flex align-items-center">
                            <input class="mb-0" type="text" wire:model="search" autocomplete="off"></input>
                            @if ($search)

                                <button class="ml-1" wire:click="clearSearch">
                                    <div class="w-2 h-2">trash</div>
                                </button>
                            @endif
                        </div>
                    @endif

                    @if($hasTrashed)
                        <div class=" ml-3 flex items-center">
                            <div class="w-10 h-10">
                                trash
                            </div>
                            <input type="checkbox" wire:model="trashed"></input>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 table-responsive bottom30">
            <div class="table-responsive">
                <table class="table">
                    <thead class="table__head">
                    <tr class="table__row">
                        @if ($HasCheckboxes)
                            <th>
                                <input type="checkbox" class="rounded" wire:model="checkAll" wire:change="toggleCheckAll">
                            </th>
                        @endif
                        @include("partials._thead_fields")

                    </tr>
                    </thead>
                    <tbody class="table__body">
                    @foreach ($rowData as $row)
                        <tr class="table__row">
                            @if($HasCheckboxes)
                                <td class=""><input type="checkbox" class="rounded" value="{{ $row->id }}" wire:model="checkedItems">{{ $row->id }}</td>
                            @endif
                            <td class="table__cell">{{ $row->id }}</td>
                            <td class="table__cell">{{ $row->title }}</td>
                            <td class="table__cell">{{ $row->user?->name }}</td>
                            <td class="table__cell">{{ $row->user?->color?->name }}</td>
                            <td class="table__cell">
                                @foreach($row->comments as $comment)
                                    <li>{{ $comment }}</li>
                                @endforeach
                            </td>
                            <td class="table__cell">
                                @foreach($row->tags as $tag)
                                    <li>{{ $tag }}</li>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if ($paginate)
                    <div>
                        <div>
                            {{ $rowData->links() }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>
