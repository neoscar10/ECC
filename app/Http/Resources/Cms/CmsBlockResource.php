<?php

namespace App\Http\Resources\Cms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Cms\CmsBlockAccessResolverService;
use App\Support\Archive\AccessIconNormalizer;

class CmsBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resolver = app(CmsBlockAccessResolverService::class);
        $user = $request->user();
        
        $access = $resolver->resolve($this->resource, $user);
        
        // Normalize Icon
        $access['message']['icon'] = AccessIconNormalizer::normalize(
            $access['reason'] ?? null, 
            $access['view_mode'] ?? 'blocked'
        );

        $isClear = ($access['view_mode'] === 'clear');
        
        // Content Redaction Logic
        // Always show Title, Subtitle, Image (Teaser parameters)
        // Hide Body, CTA if not clear
        $content = $this->content ?? [];
        
        $finalContent = [
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'image_url' => $content['image_url'] ?? null,
            // Protected fields
            'body' => $isClear ? ($content['body'] ?? null) : null,
            'cta_text' => $isClear ? ($content['cta_text'] ?? null) : null,
            'cta_url' => $isClear ? ($content['cta_url'] ?? null) : null,
        ];

        return [
            'id' => $this->id,
            'title' => $this->title, // Internal title
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'content' => $finalContent,
            'access' => $access,
        ];
    }
}
