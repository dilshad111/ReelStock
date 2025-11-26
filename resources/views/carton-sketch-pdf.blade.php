<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carton Sketch</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; }
        .company-address { font-size: 14px; }
        .logo { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
        .details { margin-bottom: 20px; }
        .sketch { text-align: center; margin: 20px 0; }
        .sketch svg { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <div class="header">
        @if($company_logo)
            <img src="{{ asset('storage/' . $company_logo) }}" alt="Company Logo" class="logo">
        @endif
        <div class="company-name">{{ $company_name }}</div>
        <div class="company-address">{{ $company_address }}</div>
    </div>

    <div class="details">
        <h3>Carton Sketch Details</h3>
        @if($customer)
            <p><strong>Customer:</strong> {{ $customer->customer_name }}</p>
        @endif
        <p><strong>FEFCO Code:</strong> {{ $fefco_code }}</p>
        <p><strong>Dimensions:</strong> Length: {{ $length }} mm, Width: {{ $width }} mm, Height: {{ $height }} mm</p>
    </div>

    <div class="sketch">
        {!! $sketch_svg !!}
    </div>
</body>
</html>
