<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FGReceipt;
use App\Models\FGDispatch;

echo "Receipt 6: " . (FGReceipt::find(6) ? "Exists" : "Does NOT exist") . "\n";
echo "Dispatch 6: " . (FGDispatch::find(6) ? "Exists" : "Does NOT exist") . "\n";
echo "Receipt 295: " . (FGReceipt::find(295) ? "Exists" : "Does NOT exist") . "\n";
echo "Dispatch 295: " . (FGDispatch::find(295) ? "Exists" : "Does NOT exist") . "\n";
