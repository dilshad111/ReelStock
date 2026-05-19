<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperQuality;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request, \App\Domains\Inventory\Actions\GetStockDashboardDataAction $action)
    {
        $timeRange = $request->get('range', 30);
        return response()->json($action->execute($timeRange));
    }

    public function managementIndex(Request $request, \App\Domains\Inventory\Actions\GetManagementDashboardDataAction $action)
    {
        $timeRange = $request->get('range', 30);
        return response()->json($action->execute($timeRange));
    }

    public function transportIndex(Request $request, \App\Domains\Inventory\Actions\GetTransportDashboardDataAction $action)
    {
        $timeRange = $request->get('range', 30);
        return response()->json($action->execute($timeRange));
    }
}
