<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MembershipTierController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $tiers = Models\MembershipTier::with(['privileges', 'features'])
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();
        
        return $this->success($tiers);
    }

    public function show($id)
    {
        $tier = app(\App\Services\Membership\MembershipTierResolver::class)->getTierWithDetails($id);
        
        if (!$tier) {
            abort(404, 'Membership Tier not found.');
        }
            
        return $this->success($tier);
    }
}
