<?php

namespace App\Http\Controllers;

use App\Models\CartonType;

class CartonTypeController extends Controller
{
    public function index()
    {
        return response()->json(
            CartonType::where('is_active', true)
                ->orderBy('standard_code')
                ->get()
        );
    }
}
