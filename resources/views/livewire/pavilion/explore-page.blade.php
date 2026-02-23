<div>
    @if(empty($vm['blocks']))
        <div class="text-center py-5">
            <span class="material-symbols-outlined text-muted fs-1 mb-3">auto_awesome_motion</span>
            <p class="text-muted">No content available at the moment. Check back soon!</p>
        </div>
    @endif

    @foreach($vm['blocks'] as $block)
        <div class="mb-4">
            @switch($block['type'])
                @case('banner')
                @case('featured_story')
                    <x-pavilion.blocks.featured-story :block="$block" />
                    @break

                @case('section_header')
                    <x-pavilion.blocks.section-header :block="$block" />
                    @break

                @case('card')
                @case('artifact')
                    @if($block['access']['view_mode'] === 'blocked')
                        <x-pavilion.blocks.locked-card :block="$block" />
                    @else
                        <x-pavilion.blocks.artifact-card :block="$block" />
                    @endif
                    @break

                @case('row')
                @case('editorial_row')
                    <x-pavilion.blocks.editorial-row :block="$block" />
                    @break

                @case('investment')
                    <x-pavilion.blocks.investment-locked :block="$block" />
                    @break

                @case('cta')
                @case('become_member')
                    <x-pavilion.blocks.become-member :block="$block" />
                    @break

                @default
                    {{-- Fallback for unhandled types --}}
                    <div class="pavilion-card p-3">
                        <p class="mb-0 text-muted small">Content type [{{ $block['type'] }}] not yet supported.</p>
                    </div>
            @endswitch
        </div>
    @endforeach
</div>
