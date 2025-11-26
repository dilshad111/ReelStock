<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\PaperQuality;

class SetupController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');
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
        Customer::truncate();
        Supplier::truncate();
        PaperQuality::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json(['message' => 'All data reset successfully']);
    }

    public function deleteTable(Request $request)
    {
        $request->validate([
            'table' => 'required|string|in:reel_receipts,reel_issues,reel_returns,reels,customers,suppliers,paper_qualities',
        ]);

        $table = $request->table;

        DB::table($table)->truncate();

        return response()->json(['message' => "Table {$table} data deleted successfully"]);
    }

    public function getTables()
    {
        $tables = [
            'reel_receipts',
            'reel_issues',
            'reel_returns',
            'reels',
            'customers',
            'suppliers',
            'paper_qualities',
        ];

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
