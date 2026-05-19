<?php

namespace App\Http\Middleware;

use Closure;
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
        $rules = [];
        $messages = [];

        // Check if both date_from and date_to are present and filled
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $rules['date_to'] = 'after_or_equal:date_from';
            $messages['date_to.after_or_equal'] = 'The To Date must not be less than the From Date.';
        }

        // Check if both from_date and to_date are present and filled
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $rules['to_date'] = 'after_or_equal:from_date';
            $messages['to_date.after_or_equal'] = 'The To Date must not be less than the From Date.';
        }

        // Check if both start_date and end_date are present and filled
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $rules['end_date'] = 'after_or_equal:start_date';
            $messages['end_date.after_or_equal'] = 'The End Date must not be less than the Start Date.';
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
}
