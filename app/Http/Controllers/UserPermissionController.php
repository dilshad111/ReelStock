<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function getPermissions($userId)
    {
        $currentUser = auth()->user();
        if ($currentUser->id != $userId && $currentUser->role?->name !== 'Admin' && $currentUser->email !== 'superadmin@qc.com') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return UserPermission::where('user_id', $userId)->get();
    }

    public function updatePermissions(Request $request, $userId)
    {
        $permissions = $request->input('permissions', []);

        // Delete existing permissions
        UserPermission::where('user_id', $userId)->delete();

        // Insert new permissions
        foreach ($permissions as $perm) {
            UserPermission::create([
                'user_id' => $userId,
                'menu' => $perm['menu'],
                'can_view' => $perm['can_view'],
                'can_add' => $perm['can_add'] ?? false,
                'can_edit' => $perm['can_edit'],
                'can_delete' => $perm['can_delete'] ?? false,
                'can_see_amounts' => $perm['can_see_amounts']
            ]);
        }

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
