<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit::with('user');

        // Deduplicate: If multiple models are audited at once for the same action, 
        // prioritize the "transaction" models (Receipt, Issue, Return) over the base Reel model.
        $query->where(function ($q) {
            $q->where('auditable_type', '!=', 'App\Models\Reel')
              ->orWhereNotExists(function ($sub) {
                  $sub->select(DB::raw(1))
                      ->from('audits as a2')
                      ->whereColumn('a2.user_id', 'audits.user_id')
                      ->whereColumn('a2.created_at', 'audits.created_at')
                      ->whereIn('a2.auditable_type', [
                          'App\Models\ReelReceipt',
                          'App\Models\ReelIssue',
                          'App\Models\ReelReturn'
                      ]);
              });
        });

        $query->orderBy('created_at', 'desc');

        // Optional filtering by event
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Optional filtering by auditable_type
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', 'like', '%' . $request->auditable_type . '%');
        }

        // Optional filtering by user (search by name or email)
        if ($request->filled('user_search')) {
            $search = $request->user_search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Keep direct ID filter for backward compatibility or precise filtering
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->paginate(50));
    }
}
