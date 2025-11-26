<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function getPermissions($userId)
    {
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
                'can_edit' => $perm['can_edit'],
                'can_see_amounts' => $perm['can_see_amounts']
            ]);
        }

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
