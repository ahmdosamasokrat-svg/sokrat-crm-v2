<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class SharePhoneMask
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $maskPhones = $user && !$user->hasPermission('leads.phone.view');
        View::share('maskPhones', $maskPhones);

        return $next($request);
    }
}
