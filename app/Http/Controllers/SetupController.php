<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use App\Models\Supplier;
use App\Models\PaperQuality;

class SetupController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        
        $sequence = \App\Models\ReelSequence::first();
        if ($sequence) {
            $settings['reel_no_prefix'] = $sequence->prefix;
            $settings['reel_next_number'] = (string) $sequence->next_number;
        }

        return response()->json($settings);
    }

    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $setting = Setting::firstOrNew(['key' => $request->key]);
        $oldValue = $setting->value;
        $setting->value = $request->value;
        $setting->save();

        if ($request->key === 'reel_no_prefix') {
            $newPrefix = $request->value ?? '';
            $oldPrefix = $oldValue ?? 'RL2026';

            if ($newPrefix && $oldPrefix && $oldPrefix !== $newPrefix) {
                Reel::where('reel_no', 'like', $oldPrefix . '%')
                    ->chunkById(200, function ($reels) use ($oldPrefix, $newPrefix) {
                        $oldLength = strlen($oldPrefix);
                        foreach ($reels as $reel) {
                            $suffix = substr($reel->reel_no, $oldLength);
                            $reel->reel_no = $newPrefix . $suffix;
                            $reel->save();
                        }
                    });
            }

            // Sync with ReelSequence table
            \App\Models\ReelSequence::query()->update(['prefix' => $newPrefix]);
        }

        if ($request->key === 'reel_next_number') {
            $newNext = (int) $request->value;
            \App\Models\ReelSequence::query()->update(['next_number' => $newNext]);
        }

        return response()->json(['message' => 'Setting updated successfully']);
    }

    public function resetAllData()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ReelReceipt::truncate();
        ReelIssue::truncate();
        ReelReturn::truncate();
        Reel::truncate();
        Supplier::truncate();
        PaperQuality::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json(['message' => 'All data reset successfully']);
    }

    public function deleteTable(Request $request)
    {
        $request->validate([
            'table' => 'required|string',
        ]);

        $table = $request->table;

        $excludedTables = [
            'migrations',
            'personal_access_tokens',
            'users',
            'roles',
            'settings',
            'user_permissions',
            'failed_jobs',
            'password_resets',
            'reel_sequences'
        ];

        if (in_array($table, $excludedTables)) {
            return response()->json(['message' => "Deleting data from this table is not allowed for safety."], 403);
        }

        // Check if table exists
        $dbName = DB::getDatabaseName();
        $tableExists = DB::select("SHOW TABLES LIKE ?", [$table]);

        if (empty($tableExists)) {
            return response()->json(['message' => "Table '{$table}' does not exist in the database."], 404);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json(['message' => "Table '{$table}' data deleted successfully"]);
    }

    public function getTables()
    {
        $allTables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";
        
        $excludedTables = [
            'migrations',
            'personal_access_tokens',
            'users',
            'roles',
            'settings',
            'user_permissions',
            'failed_jobs',
            'password_resets',
            'reel_sequences'
        ];
        
        $tables = [];
        foreach ($allTables as $table) {
            $tableName = $table->$key;
            if (!in_array($tableName, $excludedTables)) {
                $tables[] = $tableName;
            }
        }
        
        sort($tables);

        return response()->json($tables);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        $logo = $request->file('logo');
        $filename = 'company_logo.' . $logo->getClientOriginalExtension();
        $path = $logo->storeAs('logos', $filename, 'public');

        Setting::updateOrCreate(
            ['key' => 'company_logo'],
            ['value' => $path]
        );

        return response()->json(['message' => 'Logo uploaded successfully', 'path' => $path]);
    }
}
