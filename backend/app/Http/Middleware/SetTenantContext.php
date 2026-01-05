<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     * Sets the tenant context for Row-Level Security (RLS)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->account_id) {
            // Set PostgreSQL session variable for RLS
            DB::statement("SET app.current_account_id = '{$user->account_id}'");
        }

        return $next($request);
    }
}
