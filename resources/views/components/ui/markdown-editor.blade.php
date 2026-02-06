@props([
    'id',
    'value' => '',
    'height' => '220',
    'placeholder' => 'Enter text...',
    'editorKey' => 'static', // IMPORTANT: pass a changing value from Livewire on open/edit/reset
])

@php
    use Illuminate\Support\Str;

    // Extract wire:model* (wire:model, wire:model.defer, wire:model.live, etc.)
    $wireModelAttr = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($k) => Str::startsWith($k, 'wire:model'));

    $wireModel = $wireModelAttr ? $attributes->get($wireModelAttr) : null;

    if (!$wireModel) {
        throw new Exception('x-ui.markdown-editor requires a wire:model binding.');
    }
@endphp

<style>
    /* Keep toolbar readable (minimal) */
    .editor-toolbar { background:#f6f8fa !important; border-color:#d0d7de !important; }
    .editor-toolbar a, .editor-toolbar button { color:#24292f !important; opacity:1 !important; }
    .editor-toolbar a:before, .editor-toolbar button:before { color:#24292f !important; opacity:1 !important; }
    .editor-toolbar .fa:before { font-family: FontAwesome !important; }
</style>

{{-- Keyed wrapper so the editor resets correctly across edit/create/open --}}
<div wire:key="mde-{{ $id }}-{{ $editorKey }}">
    <div wire:ignore>
        <div
            x-data
            x-init="
                if (!window.EasyMDE) {
                    console.error('EasyMDE is not loaded on this page.');
                    return;
                }

                // Hard-prevent double init for same DOM node
                if ($refs.editor.dataset.mdeInit === '1') return;
                $refs.editor.dataset.mdeInit = '1';

                const mde = new EasyMDE({
                    element: $refs.editor,
                    spellChecker: false,
                    status: ['lines','words'],
                    minHeight: '{{ (int)$height }}px',
                    placeholder: @js($placeholder),
                    toolbar: [
                        'bold','italic','heading','|',
                        'quote','unordered-list','ordered-list','|',
                        'link','preview','guide'
                    ],
                    forceSync: true
                });

                // Editor -> Livewire (same as your working example)
                mde.codemirror.on('change', (cm) => {
                    @this.set(@js($wireModel), cm.getValue());
                });

                // Make sure it renders correctly in modals and caret is in the right place
                setTimeout(() => {
                    try {
                        const cm = mde.codemirror;
                        cm.refresh();
                        const lastLine = cm.lastLine();
                        const lastCh = (cm.getLine(lastLine) || '').length;
                        cm.setCursor({ line: lastLine, ch: lastCh });
                        cm.scrollIntoView({ line: lastLine, ch: lastCh }, 80);
                    } catch (e) {}
                }, 50);
            "
        >
            <textarea x-ref="editor" id="{{ $id }}">{{ $value ?? '' }}</textarea>
        </div>
    </div>
</div>
