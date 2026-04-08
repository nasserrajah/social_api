<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        
        // إذا كان الطلب JSON، حوله إلى array
        if ($request->isJson()) {
            $data = $request->json()->all();
            $request->merge($data);
        }
        
        return $next($request);
    }
}