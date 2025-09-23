<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class GateDefineMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $permissions = Permission::whereHas('roles', function ($query) {
                $query->where('roles.id', auth()->user()->role_id);
            })->get();

            foreach ($permissions as $permission) {
                Gate::define($permission->name, fn() => true);
            }
        }

        return $next($request);
    }
}
