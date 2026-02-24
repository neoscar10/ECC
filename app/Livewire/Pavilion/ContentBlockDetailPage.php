<?php

namespace App\Livewire\Pavilion;

use App\Services\Cms\CmsBlockWebService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.app')]
class ContentBlockDetailPage extends Component
{
    public array $block = [];
    public string $bodyHtml = '';

    public function mount(CmsBlockWebService $cmsService, int $id)
    {
        $user = Auth::user();
        
        // Fetch resolved block detail (includes markdown)
        $this->block = $cmsService->resolveBlockDetail($id, $user);
        
        // Render Markdown if available and allowed
        if (($this->block['access']['is_allowed'] ?? false) && !empty($this->block['detail_markdown'])) {
            $this->bodyHtml = $this->renderMarkdown($this->block['detail_markdown']);
        }
    }

    protected function renderMarkdown(?string $markdown): string
    {
        if (!$markdown) return '';

        if (method_exists(Str::class, 'markdown')) {
            return (string) Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return nl2br(e($markdown));
    }

    public function render()
    {
        return view('livewire.pavilion.content-block-detail', [
            'title' => $this->block['title'] ?? 'Content Detail',
            'activeNav' => 'explore',
        ]);
    }
}
