<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

function inspectPath($path) {
    echo "--- Inspecting $path ---\n";
    try {
        $request = \Illuminate\Http\Request::create($path, 'GET');
        $route = app('router')->getRoutes()->match($request);
        $middleware = $route->gatherMiddleware();
        
        foreach ($middleware as $index => $m) {
            $type = gettype($m);
            echo "[$index] Type: $type";
            if ($type === 'string') {
                echo " Value: $m";
            } elseif ($type === 'array') {
                echo " Value: " . json_encode($m);
            }
            echo "\n";
        }
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

inspectPath('/admin/dashboard');
inspectPath('/admin/users');
