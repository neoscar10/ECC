<?php

namespace App\Services\Pavilion;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Services\Cms\ContentBlockMobileResolver;
use Illuminate\Support\Facades\Route;

class PavilionExploreService
{
    protected ContentBlockMobileResolver $resolver;

    public function __construct(ContentBlockMobileResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the view model for the Explore page.
     */
    public function getExploreViewModel(?User $user): array
    {
        $userTier = $user?->currentMembership?->membershipTier;

        // 1. Fetch Blocks (Only Visible to User context)
        // Placement 'home' is hardcoded as per requirements for now.
        $blocks = CmsBlock::active()
            ->where('placement', 'home')
            ->visibleTo($user, $userTier)
            ->orderBy('sort_order', 'asc')
            ->get();

        // 2. Resolve Each Block using existing Mobile Resolver
        // This ensures the same restrictions and logic apply to Web.
        $resolvedBlocks = $blocks->map(function ($block) use ($user) {
            $data = $this->resolver->resolve($block, $user, true, 20);
            
            // Adapt for Web Detail Routes
            if ($data['has_detail_page'] ?? false) {
                $data['web_detail_url'] = route('pavilion.detail', [
                    'type' => $data['type'], 
                    'slugOrId' => $data['id']
                ]);
            }
            
            return $data;
        })->toArray();

        return [
            'title' => 'The Pavilion',
            'joinClubUrl' => !$user ? route('welcome') : null,
            'blocks' => $resolvedBlocks,
        ];
    }
}
