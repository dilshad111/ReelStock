<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $cartonTypes = [
        '0200' => 'Slotted-Type Carton',
        '0201' => 'Regular Slotted Carton',
        '0202' => 'Overlap Slotted Carton',
        '0203' => 'Full Overlap Slotted Carton',
        '0204' => 'Center Special Slotted Carton',
        '0205' => 'Center Special Overlap Slotted Carton',
        '0206' => 'Center Special Full Overlap Slotted Carton',
        '0207' => 'Special Slotted Carton',
        '0208' => 'Slotted Carton With Cover',
        '0209' => 'Slotted Tray Carton',
        '0300' => 'Telescope-Type Carton',
        '0301' => 'Full Telescope Carton',
        '0302' => 'Telescope Carton With Separate Lid',
        '0303' => 'Two-Piece Telescope Carton',
        '0400' => 'Folder-Type Carton',
        '0401' => 'One-Piece Folder Carton',
        '0403' => 'Folder Carton With Locking Tabs',
        '0404' => 'Five-Panel Folder Carton',
        '0405' => 'Wrap-Around Folder Carton',
        '0406' => 'Folder Tray Carton',
        '0409' => 'Folder Carton With Hinged Lid',
        '0500' => 'Slide-Type Carton',
        '0501' => 'Sleeve and Drawer Carton',
        '0502' => 'Slide Carton',
        '0503' => 'Slide Tray Carton',
        '0600' => 'Rigid-Type Carton',
        '0601' => 'Rigid Carton',
        '0602' => 'Rigid Shoulder Carton',
        '0603' => 'Rigid Carton With Lid',
        '0700' => 'Ready-Glued Carton',
        '0701' => 'Pre-Glued Lock Bottom Carton',
        '0703' => 'Auto-Bottom Carton',
        '0711' => 'Ready-Glued Tray Carton',
        '0713' => 'Ready-Glued Folder Carton',
        '0900' => 'Interior Fitment',
        '0901' => 'Partition Set',
        '0902' => 'Divider Insert',
        '0903' => 'Cell Divider',
        '0904' => 'Pad Insert',
        '0905' => 'Separator Fitment',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('carton_types')) {
            return;
        }

        $now = now();
        $records = collect($this->cartonTypes)->map(fn ($name, $code) => [
            'name' => $name,
            'standard_code' => $code,
            'preview_image' => "/images/fefco/{$code}.png",
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        DB::table('carton_types')->upsert(
            $records,
            ['standard_code'],
            ['name', 'preview_image', 'is_active', 'updated_at']
        );

        DB::table('carton_types')
            ->whereIn('standard_code', ['0201-HSC', '0427'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('carton_types')) {
            return;
        }

        DB::table('carton_types')
            ->whereIn('standard_code', array_keys($this->cartonTypes))
            ->delete();

        DB::table('carton_types')
            ->whereIn('standard_code', ['0201-HSC', '0427'])
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
