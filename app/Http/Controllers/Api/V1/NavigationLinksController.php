<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class NavigationLinksController extends Controller
{
    /**
     * Get the dynamic navigation link labels for the mobile app.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $defaultSequence = ['explore', 'archive', 'auctions', 'club', 'shop', 'profile'];
        $sequenceJson = Setting::get('nav_sequence');
        $sequence = $sequenceJson ? json_decode($sequenceJson, true) : $defaultSequence;

        foreach ($defaultSequence as $key) {
            if (!in_array($key, $sequence)) {
                $sequence[] = $key;
            }
        }

        $labels = [
            'explore' => Setting::get('nav_label_explore', 'Explore'),
            'archive' => Setting::get('nav_label_archive', 'Archive'),
            'auctions' => Setting::get('nav_label_auctions', 'Auctions'),
            'club' => Setting::get('nav_label_club', 'Club'),
            'shop' => Setting::get('nav_label_shop', 'Shop'),
            'profile' => Setting::get('nav_label_profile', 'Profile'),
        ];

        $orderedLinks = [];
        foreach ($sequence as $key) {
            if (isset($labels[$key])) {
                $orderedLinks[] = [
                    'key' => $key,
                    'label' => $labels[$key],
                ];
            }
        }

        return response()->json($orderedLinks);
    }
}
