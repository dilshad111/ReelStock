<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateDateRange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $this->normalizeDdMmmYyyyInputs($request);

        $rules = [];
        $messages = [];
        $today = Carbon::today()->format('Y-m-d');
        $maxRangeDays = 365;

        $datePairs = [
            ['from' => 'date_from', 'to' => 'date_to', 'fromLabel' => 'From Date', 'toLabel' => 'To Date'],
            ['from' => 'from_date', 'to' => 'to_date', 'fromLabel' => 'From Date', 'toLabel' => 'To Date'],
            ['from' => 'start_date', 'to' => 'end_date', 'fromLabel' => 'Start Date', 'toLabel' => 'End Date'],
            ['from' => 'from', 'to' => 'to', 'fromLabel' => 'From Date', 'toLabel' => 'To Date'],
        ];

        foreach ($datePairs as $pair) {
            $hasFrom = $request->filled($pair['from']);
            $hasTo = $request->filled($pair['to']);

            if ($hasFrom) {
                $rules[$pair['from']] = ['date_format:Y-m-d', 'before_or_equal:' . $today];
                $messages[$pair['from'] . '.date_format'] = "The {$pair['fromLabel']} is invalid.";
                $messages[$pair['from'] . '.before_or_equal'] = 'Future dates are not allowed.';
            }

            if ($hasTo) {
                $rules[$pair['to']] = ['date_format:Y-m-d', 'before_or_equal:' . $today];
                $messages[$pair['to'] . '.date_format'] = "The {$pair['toLabel']} is invalid.";
                $messages[$pair['to'] . '.before_or_equal'] = 'Future dates are not allowed.';
            }

            if ($hasFrom xor $hasTo) {
                $field = $hasFrom ? $pair['to'] : $pair['from'];
                $rules[$field] = array_merge($rules[$field] ?? [], ['required']);
                $messages[$field . '.required'] = 'Please select both From Date and To Date.';
            }

            if ($hasFrom && $hasTo) {
                $rules[$pair['to']][] = 'after_or_equal:' . $pair['from'];
                $messages[$pair['to'] . '.after_or_equal'] = "The {$pair['toLabel']} must not be less than the {$pair['fromLabel']}.";

                try {
                    $from = Carbon::createFromFormat('Y-m-d', (string) $request->input($pair['from']))->startOfDay();
                    $to = Carbon::createFromFormat('Y-m-d', (string) $request->input($pair['to']))->startOfDay();

                    if ($from->diffInDays($to) > $maxRangeDays) {
                        return response()->json([
                            'message' => "Date range cannot exceed {$maxRangeDays} days.",
                            'errors' => [
                                $pair['to'] => ["Date range cannot exceed {$maxRangeDays} days."]
                            ]
                        ], 422);
                    }
                } catch (\Throwable $e) {
                    // Let validator handle format issues.
                }
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        return $next($request);
    }

    private function normalizeDdMmmYyyyInputs(Request $request): void
    {
        $supportedKeys = [
            'date_from',
            'date_to',
            'from_date',
            'to_date',
            'start_date',
            'end_date',
            'from',
            'to',
        ];

        $payload = $request->all();

        foreach ($supportedKeys as $key) {
            if (!isset($payload[$key]) || !is_string($payload[$key])) {
                continue;
            }

            $value = trim($payload[$key]);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^\d{2}-[A-Za-z]{3}-\d{4}$/', $value) !== 1) {
                continue;
            }

            try {
                $parsed = Carbon::createFromFormat('d-M-Y', $value);
                if ($parsed->format('d-M-Y') === $value) {
                    $payload[$key] = $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Keep original value so validator returns a clean error.
            }
        }

        $request->merge($payload);
    }
}
