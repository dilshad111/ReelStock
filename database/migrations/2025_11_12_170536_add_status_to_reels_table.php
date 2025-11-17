<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $newStatuses = ['in_stock', 'partially_used', 'fully_used', 'issued', 'returned'];

        if (!Schema::hasColumn('reels', 'status')) {
            Schema::table('reels', function (Blueprint $table) use ($newStatuses) {
                $table->enum('status', $newStatuses)->default('in_stock');
            });
            return;
        }

        $statusList = "'" . implode("','", $newStatuses) . "'";
        DB::statement("ALTER TABLE reels MODIFY COLUMN status ENUM({$statusList}) NOT NULL DEFAULT 'in_stock'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('reels', 'status')) {
            return;
        }

        $originalStatuses = ['in_stock', 'partially_used', 'fully_used'];
        $statusList = "'" . implode("','", $originalStatuses) . "'";
        DB::statement("ALTER TABLE reels MODIFY COLUMN status ENUM({$statusList}) NOT NULL DEFAULT 'in_stock'");
    }
};
