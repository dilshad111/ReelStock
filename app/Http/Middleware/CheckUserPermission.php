<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $menu
     * @param  string  $action
     */
    public function handle(Request $request, Closure $next, string $menu, string $action = 'auto'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Superadmin bypasses permission checks
        if ($user->email === 'superadmin@qc.com') {
            return $next($request);
        }

        // Resolve action if set to 'auto'
        if ($action === 'auto') {
            $method = $request->method();
            $path = $request->path();
            
            // Check if it's a read-only query/fetch/report even if it's a POST
            $isReadQuery = preg_match('/(fetch|search|report|history|compare|triggered|resolve|calculate)/i', $path);

            if ($method === 'GET' || $isReadQuery) {
                $action = 'view';
            } elseif ($method === 'POST') {
                $action = 'add';
            } elseif (in_array($method, ['PUT', 'PATCH'])) {
                $action = 'edit';
            } elseif ($method === 'DELETE') {
                $action = 'delete';
            } else {
                $action = 'view';
            }
        }

        // Get matching permission for the user and menu
        $permission = \App\Models\UserPermission::where('user_id', $user->id)
            ->where('menu', $menu)
            ->first();

        if (!$permission) {
            // Default check if user is Admin and no custom permissions exist to avoid lock-outs
            if ($user->role?->name === 'Admin') {
                return $next($request);
            }
            return response()->json(['message' => 'Unauthorized access (no permissions defined).'], 403);
        }

        // Check matching action permission
        $isAllowed = false;
        switch ($action) {
            case 'view':
                $isAllowed = $permission->can_view;
                break;
            case 'add':
                $isAllowed = $permission->can_add;
                break;
            case 'edit':
                $isAllowed = $permission->can_edit;
                break;
            case 'delete':
                $isAllowed = $permission->can_delete;
                break;
            case 'amounts':
                $isAllowed = $permission->can_see_amounts;
                break;
        }

        if (!$isAllowed) {
            return response()->json(['message' => 'Forbidden: You do not have permissions to perform this action.'], 403);
        }

        return $next($request);
    }
}
