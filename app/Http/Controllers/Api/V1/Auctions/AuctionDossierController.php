<?php

namespace App\Http\Controllers\Api\V1\Auctions;

use App\Http\Controllers\Controller;
use App\Services\Auctions\AuctionDossierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuctionDossierController extends Controller
{
    protected $service;

    public function __construct(AuctionDossierService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:50'
        ]);

        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $perPage = $request->input('per_page', 10);
        $dossier = $this->service->getDossier($user, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Auction dossier retrieved successfully.',
            'data' => $dossier->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $dossier->currentPage(),
                    'last_page' => $dossier->lastPage(),
                    'total' => $dossier->total(),
                    'per_page' => $dossier->perPage(),
                ]
            ]
        ]);
    }
}
