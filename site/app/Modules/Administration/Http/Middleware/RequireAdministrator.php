<?php

namespace App\Modules\Administration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->guest(route('admin.login'));
        }

        abort_unless(Auth::guard('admin')->user()->email === 'supp.manobank@mail.ru', 403);

        return $next($request);
    }
}
