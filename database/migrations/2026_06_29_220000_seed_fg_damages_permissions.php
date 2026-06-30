<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fetch all user IDs
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Seed fg-damages permission
            $existsDmg = DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->where('menu', 'fg-damages')
                ->exists();

            if (!$existsDmg) {
                // Fetch user's fg-products permissions to match or fallback to true
                $prodPerm = DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('menu', 'fg-products')
                    ->first();

                DB::table('user_permissions')->insert([
                    'user_id' => $user->id,
                    'menu' => 'fg-damages',
                    'can_view' => $prodPerm ? $prodPerm->can_view : true,
                    'can_add' => $prodPerm ? $prodPerm->can_add : true,
                    'can_edit' => $prodPerm ? $prodPerm->can_edit : true,
                    'can_delete' => $prodPerm ? $prodPerm->can_delete : true,
                    'can_see_amounts' => $prodPerm ? $prodPerm->can_see_amounts : true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed sample-submissions permission
            $existsSample = DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->where('menu', 'sample-submissions')
                ->exists();

            if (!$existsSample) {
                $prodPerm = DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('menu', 'fg-products')
                    ->first();

                DB::table('user_permissions')->insert([
                    'user_id' => $user->id,
                    'menu' => 'sample-submissions',
                    'can_view' => $prodPerm ? $prodPerm->can_view : true,
                    'can_add' => $prodPerm ? $prodPerm->can_add : true,
                    'can_edit' => $prodPerm ? $prodPerm->can_edit : true,
                    'can_delete' => $prodPerm ? $prodPerm->can_delete : true,
                    'can_see_amounts' => $prodPerm ? $prodPerm->can_see_amounts : true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('user_permissions')->whereIn('menu', ['fg-damages', 'sample-submissions'])->delete();
    }
};
