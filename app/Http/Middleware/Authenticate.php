<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // إذا الطلب يتوقع JSON (API)، لا تعيد توجيه، فقط ارجع null
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // للويب فقط (يمكنك حذفها إذا لم تستخدم واجهة ويب)
        // return route('login');
        return null;
    }
}
