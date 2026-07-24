@props(['blocks' => [], 'placement' => 'explore'])

<div {{ $attributes->merge(['class' => 'cms-placement-zone cms-zone-' . $placement]) }}>
    @forelse($blocks as $block)
        <div class="cms-block-wrapper mb-4" data-block-id="{{ $block['id'] }}" data-block-type="{{ $block['type'] }}">
            @switch($block['type'])
                @case('banner')
                    <x-cms.blocks.banner :block="$block" />
                    @break

                @case('card')
                    <x-cms.blocks.card :block="$block" />
                    @break

                @case('slider')
                    <x-cms.blocks.slider :block="$block" />
                    @break

                @case('text')
                    <x-cms.blocks.text :block="$block" />
                    @break

                @default
                    <div class="alert alert-warning small">Unknown block type: {{ $block['type'] }}</div>
            @endswitch
        </div>
    @empty
        {{-- Optional: could show nothing or a placeholder --}}
    @endforelse
</div>

@if($placement === 'home-hero')
    <style>
        .cms-zone-home-hero .cms-block-wrapper {
            margin-bottom: 2rem !important;
        }
        .cms-zone-home-hero .cms-section-heading h4 {
            font-size: 1.75rem;
        }
    </style>
@endif
