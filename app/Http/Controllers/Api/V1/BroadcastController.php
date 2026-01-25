<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        // Ensure the user is authenticated (handled by middleware, but good to check)
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'socket_id' => 'required|string',
            'channel_name' => 'required|string',
        ]);

        // Standard Laravel Broadcast Auth
        // This will call the callbacks defined in routes/channels.php
        return Broadcast::auth($request);
    }
}
