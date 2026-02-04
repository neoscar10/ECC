@props(['category', 'currentFolderId', 'expandedIds', 'depth' => 0])

@php
    $isCurrent = $category->id == $currentFolderId;
    $isExpanded = in_array($category->id, $expandedIds);
    $hasChildren = $category->children_count > 0;
@endphp

<li class="nav-item">
    <div class="nav-link py-1 px-2 d-flex align-items-center gap-1 {{ $isCurrent ? 'active bg-primary-subtle text-primary fw-medium' : 'text-body' }}"
         style="cursor: pointer; padding-left: {{ $depth * 15 + 10 }}px;"
         wire:click="openFolder({{ $category->id }})">
        
        {{-- Expand Arrow --}}
        @if($hasChildren)
            <span class="flex-shrink-0 text-muted" style="width: 16px; text-align: center;" 
                  wire:click.stop="toggleExpand({{ $category->id }})">
                @if($isExpanded)
                    <i class="ri-arrow-down-s-fill"></i>
                @else
                    <i class="ri-arrow-right-s-fill"></i>
                @endif
            </span>
        @else
            <span class="flex-shrink-0" style="width: 16px;"></span>
        @endif

        {{-- Folder Icon --}}
        <span class="flex-shrink-0">
            @if($isExpanded || $isCurrent)
                <i class="ri-folder-open-fill {{ $isCurrent ? 'text-primary' : 'text-warning' }}"></i>
            @else
                <i class="ri-folder-fill {{ $isCurrent ? 'text-primary' : 'text-warning' }}"></i>
            @endif
        </span>

        {{-- Name --}}
        <span class="flex-grow-1 text-truncate">{{ $category->name }}</span>
    </div>

    {{-- Recursion --}}
    @if($isExpanded && $hasChildren)
        <ul class="nav flex-column ps-0">
            @foreach($category->children as $child)
                @include('livewire.admin.shop.categories.partials._tree-node', [
                    'category' => $child, 
                    'currentFolderId' => $currentFolderId, 
                    'expandedIds' => $expandedIds,
                    'depth' => $depth + 1
                ])
            @endforeach
        </ul>
    @endif
</li>
