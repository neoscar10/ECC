<?php

namespace App\Services\Pavilion;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Services\Cms\ContentBlockMobileResolver;
use Illuminate\Support\Str;

class PavilionContentService
{
    protected ContentBlockMobileResolver $resolver;

    public function __construct(ContentBlockMobileResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the view model for a content detail page.
     */
    public function getDetailViewModel(?User $user, string $type, string $id): array
    {
        $userTier = $user?->currentMembership?->membershipTier;

        // Fetch the block
        $block = CmsBlock::active()
            ->visibleTo($user, $userTier)
            ->findOrFail($id);

        // Resolve using mobile resolver (includes detail markdown if clear)
        $data = $this->resolver->resolve($block, $user, true, 20, true);
        
        $isLocked = !($data['access']['is_allowed'] ?? false);
        $html = '';

        if (!$isLocked && isset($data['detail_markdown'])) {
            $html = $this->renderMarkdown($data['detail_markdown']);
        }

        return [
            'id' => $data['id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'badge' => $data['badge_text'],
            'media' => $data['media'],
            'access' => $data['access'],
            'is_locked' => $isLocked,
            'body_html' => $html,
            'content' => $block->content ?? [], // Raw content if needed
        ];
    }

    /**
     * Render markdown to safe HTML.
     */
    protected function renderMarkdown(?string $markdown): string
    {
        if (!$markdown) return '';

        // Prioritize Laravel's built-in Str::markdown (available in newer Laravel/CommonMark)
        if (method_exists(Str::class, 'markdown')) {
            return (string) Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        // Fallback for older versions if needed (though project seems modern)
        return nl2br(e($markdown));
    }
}
