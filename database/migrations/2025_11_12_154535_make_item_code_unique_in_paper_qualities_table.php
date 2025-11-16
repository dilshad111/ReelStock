<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Fill item_codes for existing rows if column exists and has nulls
        if (Schema::hasColumn('paper_qualities', 'item_code')) {
            $qualities = \DB::table('paper_qualities')->whereNull('item_code')->get();
            foreach ($qualities as $quality) {
                $words = explode(' ', $quality->quality);
                $prefix = '';
                for ($i = 0; $i < min(3, count($words)); $i++) {
                    $prefix .= strtoupper(substr($words[$i], 0, 1));
                }
                $lastCode = \DB::table('paper_qualities')->where('item_code', 'like', $prefix . '%')->orderBy('item_code', 'desc')->first();
                $nextNum = $lastCode ? intval(substr($lastCode->item_code, strlen($prefix))) + 1 : 1;
                $itemCode = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                \DB::table('paper_qualities')->where('id', $quality->id)->update(['item_code' => $itemCode]);
            }
        }

        Schema::table('paper_qualities', function (Blueprint $table) {
            if (!Schema::hasColumn('paper_qualities', 'item_code')) {
                $table->string('item_code')->unique()->after('quality');
            } else {
                $table->string('item_code')->unique()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('paper_qualities', function (Blueprint $table) {
            $table->dropUnique(['item_code']);
            $table->string('item_code')->nullable()->change();
        });
    }
};
