<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Customer;
use App\Models\Setting;

class CartonSketchController extends Controller
{
    public function exportPdf(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'fefco_code' => 'required|string',
            'length' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'sketch_svg' => 'required|string', // SVG of the sketch
        ]);

        $customer = $data['customer_id'] ? Customer::find($data['customer_id']) : null;

        $company_name = Setting::where('key', 'company_name')->value('value') ?? 'QUALITY CARTONS (PVT.) LTD.';
        $company_address = Setting::where('key', 'company_address')->value('value') ?? 'Plot# 46, Sector 24, Korangi Industrial Area Karachi';
        $company_logo = Setting::where('key', 'company_logo')->value('value');

        $pdf = Pdf::loadView('carton-sketch-pdf', [
            'customer' => $customer,
            'fefco_code' => $data['fefco_code'],
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'sketch_svg' => $data['sketch_svg'],
            'company_name' => $company_name,
            'company_address' => $company_address,
            'company_logo' => $company_logo,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('carton_sketch_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
