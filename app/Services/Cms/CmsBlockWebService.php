<?php

namespace App\Services\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use Illuminate\Support\Collection;

class CmsBlockWebService
{
    protected $mobileResolver;

    public function __construct(ContentBlockMobileResolver $mobileResolver)
    {
        $this->mobileResolver = $mobileResolver;
    }

    /**
     * Resolve all active blocks for a specific placement.
     *
     * @param string $placement
     * @param User|null $user
     * @return Collection
     */
    public function resolveBlocksForPlacement(string $placement, ?User $user): Collection
    {
        $blocks = CmsBlock::active()
            ->where('placement', $placement)
            ->orderBy('sort_order', 'asc')
            ->get();

        return $blocks->map(function (CmsBlock $block) use ($user) {
            return $this->mobileResolver->resolve(
                $block, 
                $user, 
                true, // includeItems
                15,   // itemLimit
                false // includeDetail (detail fetched via dedicated route)
            );
        });
    }

    /**
     * Resolve a single block with full detail content.
     *
     * @param int $blockId
     * @param User|null $user
     * @return array
     */
    public function resolveBlockDetail(int $blockId, ?User $user): array
    {
        $block = CmsBlock::active()->findOrFail($blockId);

        return $this->mobileResolver->resolve(
            $block,
            $user,
            true, // includeItems
            20,   // itemLimit
            true  // includeDetail (include markdown)
        );
    }
}
