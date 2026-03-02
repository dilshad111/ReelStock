<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\DB;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Models\ReelIssue;
use App\Models\ReelReturn;

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
            $searchTerm = str_replace(' ', '', $request->auditable_type);
            $query->where('auditable_type', 'like', '%' . $searchTerm . '%');
        }

        // Optional filtering by user (search by name or email)
        if ($request->filled('user_search')) {
            $search = strtolower($request->user_search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ['%' . $search . '%'])
                  ->orWhereRaw('LOWER(email) like ?', ['%' . $search . '%']);
            });
        }

        // Keep direct ID filter for backward compatibility or precise filtering
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $result = $query->paginate(50);

        // Enrich each audit record with reel_no and parsed user agent
        $result->getCollection()->transform(function ($audit) {
            // Resolve reel_no from the auditable model
            $audit->reel_no = $this->resolveReelNo($audit);

            // Parse user_agent into a human-readable string
            $audit->parsed_user_agent = $this->parseUserAgent($audit->user_agent);

            return $audit;
        });

        return response()->json($result);
    }

    /**
     * Resolve the Reel No. from the auditable model.
     * For Reel model: directly fetch reel_no.
     * For ReelReceipt/ReelIssue/ReelReturn: fetch via ->reel->reel_no.
     * Falls back to checking new_values/old_values for reel_id.
     */
    private function resolveReelNo($audit)
    {
        $type = $audit->auditable_type;
        $id = $audit->auditable_id;

        // Direct Reel model
        if ($type === 'App\Models\Reel') {
            $reel = Reel::find($id);
            return $reel ? $reel->reel_no : null;
        }

        // Transaction models that have a reel_id relationship
        $modelMap = [
            'App\Models\ReelReceipt' => ReelReceipt::class,
            'App\Models\ReelIssue'   => ReelIssue::class,
            'App\Models\ReelReturn'  => ReelReturn::class,
        ];

        if (isset($modelMap[$type])) {
            $model = $modelMap[$type]::with('reel')->find($id);
            if ($model && $model->reel) {
                return $model->reel->reel_no;
            }

            // Fallback: check audit old/new values for reel_id
            $reelId = $audit->new_values['reel_id'] ?? $audit->old_values['reel_id'] ?? null;
            if ($reelId) {
                $reel = Reel::find($reelId);
                return $reel ? $reel->reel_no : null;
            }
        }

        return null;
    }

    /**
     * Parse a user_agent string into a human-readable browser & OS summary.
     */
    private function parseUserAgent($ua)
    {
        if (empty($ua)) {
            return 'Unknown';
        }

        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // Detect browser
        if (preg_match('/Edg[e\/]?([\d.]+)/i', $ua)) {
            preg_match('/Edg[e\/]?([\d.]+)/i', $ua, $m);
            $browser = 'Edge ' . ($m[1] ?? '');
        } elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Opera ' . $m[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/Safari\/([\d.]+)/i', $ua) && preg_match('/Version\/([\d.]+)/i', $ua, $m)) {
            $browser = 'Safari ' . $m[1];
        } elseif (preg_match('/MSIE ([\d.]+)/i', $ua, $m) || preg_match('/Trident.*rv:([\d.]+)/i', $ua, $m)) {
            $browser = 'IE ' . $m[1];
        }

        // Simplify browser version to major.minor
        if (preg_match('/^(.+?)\s([\d]+\.[\d]+)/', $browser, $bm)) {
            $browser = $bm[1] . ' ' . $bm[2];
        }

        // Detect OS
        if (preg_match('/Windows NT 10/i', $ua)) {
            $os = 'Windows 10+';
        } elseif (preg_match('/Windows NT 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6\.2/i', $ua)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([\d.]+)/i', $ua, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/iPhone|iPad/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        // Add Device/PC Name guess if possible (basic heuristic)
        $pcName = 'Unknown Device';
        if (preg_match('/Windows NT \d+\.\d+; (Win64; x64|WOW64)(?:; Trident\/\d+\.\d+)?(?:; rv:\d+\.\d+)?\) (?:like Gecko)?(?:.*)/i', $ua)) {
           // Not much info for PC name in UA, OS replaces it.
        }

        return $browser . ' / ' . $os;
    }
}

