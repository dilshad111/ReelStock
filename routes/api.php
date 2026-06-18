<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PaperQualityController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\ReelReceiptController;
use App\Http\Controllers\ReelIssueController;
use App\Http\Controllers\ReelReturnController;
use App\Http\Controllers\ReelTransferController;
use App\Http\Controllers\MonthlyConsumptionReportController;
use App\Http\Controllers\ReelStockReportController;
use App\Http\Controllers\ReelReceiptReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyClosingReportController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ReelUsageReportController;
use App\Http\Controllers\OldReelsReportController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CartageRateController;
use App\Http\Controllers\CartageBillController;
use App\Http\Controllers\CartageReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FGReceiptController;
use App\Http\Controllers\FGDispatchController;
use App\Http\Controllers\FGReportController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\QcInspectionController;
use App\Http\Controllers\PaperColorController;
use App\Http\Controllers\RMItemController;
use App\Http\Controllers\RMItemSupplierRateController;
use App\Http\Controllers\RMCategoryController;
use App\Http\Controllers\RMReceiptController;
use App\Http\Controllers\RMConsumptionController;
use App\Http\Controllers\RMDashboardController;
use App\Http\Controllers\RMReportController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\JobIssueController;
use App\Http\Controllers\ProductionConfigurationController;
use App\Http\Controllers\CartonTypeController;
use App\Http\Controllers\ProductEngineeringController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'user']);
Route::middleware('auth:sanctum')->post('/user/profile', [AuthController::class, 'updateProfile']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Users
    Route::apiResource('users', UserController::class);

    // Roles
    Route::get('roles', [RoleController::class, 'index']);

    // Paper Colors
    Route::apiResource('paper-colors', PaperColorController::class);
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);

    // Paper Qualities
    Route::apiResource('paper-qualities', PaperQualityController::class);

    // Reel Receipts
    Route::apiResource('reel-receipts', ReelReceiptController::class);
    Route::post('reel-receipts/bulk', [ReelReceiptController::class, 'bulkStore']);

    // Reel Issues
    Route::apiResource('reel-issues', ReelIssueController::class);
    Route::get('fetch-reel/{reel_no}', [ReelIssueController::class, 'fetchReel']);

    // Reel Returns
    Route::apiResource('reel-returns', ReelReturnController::class);
    Route::get('fetch-reel-return/{reel_no}', [ReelReturnController::class, 'fetchReel']);

    // Reel Location Transfers
    Route::get('reel-transfers/fetch-reel/{reel_no}', [ReelTransferController::class, 'fetchReel']);
    Route::apiResource('reel-transfers', ReelTransferController::class)->only(['index', 'store']);

    // Reports
    Route::get('reports/monthly-consumption', [MonthlyConsumptionReportController::class, 'index']);
    Route::get('reports/reel-stock', [ReelStockReportController::class, 'index']);
    Route::get('reports/reel-stock/sizes', [ReelStockReportController::class, 'getAvailableSizes']);
    Route::get('reports/reel-stock/qualities', [ReelStockReportController::class, 'getAvailableQualities']);
    Route::get('reports/reel-stock/suppliers', [ReelStockReportController::class, 'getAvailableSuppliers']);
    Route::get('reports/reel-stock/{reel_no}/history', [ReelStockReportController::class, 'getReelHistory']);
    Route::get('reports/reel-stock-count', [ReelStockReportController::class, 'getReelStockCount']);
    Route::get('reports/reel-receipt', [ReelReceiptReportController::class, 'index']);
    Route::get('reports/monthly-closing', [MonthlyClosingReportController::class, 'index']);
    Route::get('reports/audits', [AuditController::class, 'index']);
    Route::get('reports/usage-intelligence', [ReelUsageReportController::class, 'usageIntelligence']);
    Route::get('reports/predictive-analytics', [ReelUsageReportController::class, 'predictiveAnalytics']);
    Route::get('reports/old-reels', [OldReelsReportController::class, 'index']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('management-dashboard', [DashboardController::class, 'managementIndex']);
    Route::get('transport-dashboard', [DashboardController::class, 'transportIndex']);

    // User Permissions
    Route::get('user-permissions/{userId}', [UserPermissionController::class, 'getPermissions']);
    Route::post('user-permissions/{userId}', [UserPermissionController::class, 'updatePermissions']);

    // Setup (Admin only)
    Route::prefix('setup')->group(function () {
        Route::get('settings', [SetupController::class, 'getSettings']);
        Route::post('settings', [SetupController::class, 'updateSetting']);
        Route::post('reset-all', [SetupController::class, 'resetAllData']);
        Route::post('delete-table', [SetupController::class, 'deleteTable']);
        Route::get('tables', [SetupController::class, 'getTables']);
        Route::post('upload-logo', [SetupController::class, 'uploadLogo']);
    });

    // Stock Alerts
    Route::get('stock-alerts', [StockAlertController::class, 'index']);
    Route::post('stock-alerts', [StockAlertController::class, 'store']);
    Route::put('stock-alerts/{id}', [StockAlertController::class, 'update']);
    Route::delete('stock-alerts/{id}', [StockAlertController::class, 'destroy']);
    Route::post('stock-alerts/{id}/toggle', [StockAlertController::class, 'toggle']);
    Route::get('stock-alerts/triggered', [StockAlertController::class, 'getTriggeredAlerts']);

    // Reconciliation
    Route::post('reconciliation/run', [ReconciliationController::class, 'runFullReconciliation']);
    Route::get('reconciliation/history', [ReconciliationController::class, 'getReconciliationHistory']);
    Route::get('reconciliation/discrepancies', [ReconciliationController::class, 'getDiscrepancies']);
    Route::post('reconciliation/reel/{id}', [ReconciliationController::class, 'reconcileSpecificReel']);

    // Transport Module
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customerId}/addresses', [CustomerController::class, 'storeAddress']);
    Route::put('customers/addresses/{addressId}', [CustomerController::class, 'updateAddress']);
    Route::delete('customers/addresses/{addressId}', [CustomerController::class, 'destroyAddress']);

    Route::apiResource('transporters', TransporterController::class);
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('vehicle-types', VehicleTypeController::class);

    Route::get('cartage-rates', [CartageRateController::class, 'index']);
    Route::post('cartage-rates', [CartageRateController::class, 'store']);
    Route::get('cartage-rates/classifications', [CartageRateController::class, 'getClassifications']);
    Route::post('cartage-rates/bulk-update', [CartageRateController::class, 'bulkUpdate']);
    Route::get('cartage-rates/increment-history', [CartageRateController::class, 'getIncrementHistory']);
    Route::delete('cartage-rates/increment-history/{id}', [CartageRateController::class, 'destroyIncrementHistory']);
    Route::get('cartage-rates/fetch', [CartageRateController::class, 'getRate']);
    Route::delete('cartage-rates/{id}', [CartageRateController::class, 'destroy']);

    Route::get('reports/cartage', [CartageReportController::class, 'index']);
    Route::get('reports/cartage/filters', [CartageReportController::class, 'getFilters']);
    Route::get('reports/cartage/fuel-cost', [CartageReportController::class, 'fuelCost']);

    Route::get('cartage-bills/pending-count', [CartageBillController::class, 'getPendingCount']);
    Route::get('cartage-bills/next-id', [CartageBillController::class, 'getNextId']);
    Route::post('cartage-bills/{id}/approve', [CartageBillController::class, 'approve']);
    Route::post('cartage-bills/{id}/unapprove', [CartageBillController::class, 'unapprove']);
    Route::apiResource('cartage-bills', CartageBillController::class);

    // Finished Goods Inventory Module
    Route::apiResource('products', ProductController::class);
    Route::get('products/by-customer/{customerId}', [ProductController::class, 'byCustomer']);
    Route::get('products/customer-items/{customerId}', [ProductController::class, 'customerItems']);

    Route::apiResource('fg-receipts', FGReceiptController::class);
    Route::apiResource('fg-dispatches', FGDispatchController::class);
    Route::get('fg-dispatches/available-stock/{productId}', [FGDispatchController::class, 'getAvailableStock']);
    Route::get('fg-dispatches/job-details/{jobNumber}', [FGDispatchController::class, 'getJobDetails']);
    Route::get('fg-dispatches/product-details/{productId}', [FGDispatchController::class, 'getProductDetails']);

    Route::get('fg-reports/stock', [FGReportController::class, 'stockReport']);
    Route::get('fg-reports/inventory-email', [FGReportController::class, 'inventoryEmailReport']);
    Route::get('fg-reports/job', [FGReportController::class, 'jobReport']);
    Route::get('fg-reports/job-detail', [FGReportController::class, 'jobDetail']);
    Route::get('fg-reports/audit', [FGReportController::class, 'auditReport']);
    Route::get('fg-reports/filters', [FGReportController::class, 'getFilters']);
    Route::get('fg-dashboard', [FGReportController::class, 'dashboard']);

    // QC Inspection Module
    Route::get('qc-inspections/open-lots', [QcInspectionController::class, 'openLots']);
    Route::get('qc-inspections/lot-details/{lotNumber}', [QcInspectionController::class, 'getLotDetails']);
    Route::get('qc-inspections/{id}/report', [QcInspectionController::class, 'report']);
    Route::get('qc-inspections/{id}/report-pdf', [QcInspectionController::class, 'reportPdf']);
    Route::apiResource('qc-inspections', QcInspectionController::class);

    // Raw Material Inventory Module
    Route::apiResource('rm-categories', RMCategoryController::class);
    Route::get('rm-subcategories', [RMCategoryController::class, 'subcategories']);
    Route::post('rm-subcategories', [RMCategoryController::class, 'storeSubcategory']);
    Route::put('rm-subcategories/{rmSubcategory}', [RMCategoryController::class, 'updateSubcategory']);
    Route::delete('rm-subcategories/{rmSubcategory}', [RMCategoryController::class, 'destroySubcategory']);
    Route::apiResource('rm-items', RMItemController::class);
    Route::apiResource('rm-item-supplier-rates', RMItemSupplierRateController::class)->except(['show']);
    Route::post('rm-item-supplier-rates/resolve', [RMItemSupplierRateController::class, 'resolve']);
    Route::get('rm-receipts/next-grn', [RMReceiptController::class, 'nextGrnNo']);
    Route::apiResource('rm-receipts', RMReceiptController::class);
    Route::apiResource('rm-consumptions', RMConsumptionController::class);
    Route::get('rm-dashboard', [RMDashboardController::class, 'index']);
    
    Route::prefix('rm-reports')->group(function() {
        Route::get('inventory', [RMReportController::class, 'currentInventory']);
        Route::get('ledger', [RMReportController::class, 'stockLedger']);
        Route::get('receiving', [RMReportController::class, 'receivingReport']);
        Route::get('consumption', [RMReportController::class, 'consumptionReport']);
        Route::get('reorder-requirement', [RMReportController::class, 'reorderRequirement']);
        Route::get('cost-by-category', [RMReportController::class, 'materialCostByCategory']);
        Route::get('consumption-analysis', [RMReportController::class, 'consumptionAnalysis']);
    });

    // Production / Job Card Routes
    Route::get('/carton-types', [CartonTypeController::class, 'index']);
    Route::get('/product-engineering/lookups', [ProductEngineeringController::class, 'lookups']);
    Route::get('/product-engineering/{productEngineering}/explode', [ProductEngineeringController::class, 'explode']);
    Route::apiResource('/product-engineering', ProductEngineeringController::class);
    Route::get('/job-cards', [JobCardController::class, 'index']);
    Route::get('/job-cards/next-number', [JobCardController::class, 'nextNumber']);
    Route::get('/job-cards/dashboard', [JobCardController::class, 'dashboard']);
    Route::get('/job-cards/active-list', [JobCardController::class, 'activeJobCards']);
    Route::get('/job-cards/{id}', [JobCardController::class, 'show']);
    Route::get('/job-cards/{id}/versions', [JobCardController::class, 'versionHistory']);
    Route::get('/job-cards/{id}/versions/compare', [JobCardController::class, 'compareVersions']);
    Route::get('/job-cards/{id}/versions/{versionId}', [JobCardController::class, 'showVersion']);
    Route::post('/job-cards', [JobCardController::class, 'store']);
    Route::put('/job-cards/{id}', [JobCardController::class, 'update']);
    Route::delete('/job-cards/{id}', [JobCardController::class, 'destroy']);
    Route::post('/job-cards/{id}/change-request', [JobCardController::class, 'changeRequest']);
    Route::put('/job-cards/{id}/status', [JobCardController::class, 'updateStatus']);

    Route::prefix('job-issues')->group(function () {
        Route::get('lookups', [JobIssueController::class, 'lookups']);
        Route::get('reports/summary', [JobIssueController::class, 'report']);
        Route::get('customer/{customerId}/job-cards', [JobIssueController::class, 'jobCardsByCustomer']);
        Route::get('/', [JobIssueController::class, 'index']);
        Route::post('/', [JobIssueController::class, 'store']);
        Route::get('{jobIssue}', [JobIssueController::class, 'show']);
        Route::post('{jobIssue}/start', [JobIssueController::class, 'start']);
        Route::post('{jobIssue}/entries', [JobIssueController::class, 'storeEntry']);
        Route::post('{jobIssue}/complete-stage', [JobIssueController::class, 'completeStage']);
        Route::post('{jobIssue}/complete-job', [JobIssueController::class, 'completeJob']);
    });

    // Production Configuration Module
    Route::prefix('production-config')->group(function () {
        Route::get('lookups', [ProductionConfigurationController::class, 'lookups']);

        Route::get('printing-colors', [ProductionConfigurationController::class, 'printingColors']);
        Route::post('printing-colors', [ProductionConfigurationController::class, 'storePrintingColor']);
        Route::put('printing-colors/{printingColor}', [ProductionConfigurationController::class, 'updatePrintingColor']);
        Route::delete('printing-colors/{printingColor}', [ProductionConfigurationController::class, 'destroyPrintingColor']);

        Route::get('departments', [ProductionConfigurationController::class, 'departments']);
        Route::post('departments', [ProductionConfigurationController::class, 'storeDepartment']);
        Route::put('departments/{department}', [ProductionConfigurationController::class, 'updateDepartment']);
        Route::delete('departments/{department}', [ProductionConfigurationController::class, 'destroyDepartment']);

        Route::get('machines', [ProductionConfigurationController::class, 'machines']);
        Route::post('machines', [ProductionConfigurationController::class, 'storeMachine']);
        Route::put('machines/{machine}', [ProductionConfigurationController::class, 'updateMachine']);
        Route::delete('machines/{machine}', [ProductionConfigurationController::class, 'destroyMachine']);

        Route::get('operators', [ProductionConfigurationController::class, 'operators']);
        Route::post('operators', [ProductionConfigurationController::class, 'storeOperator']);
        Route::put('operators/{operator}', [ProductionConfigurationController::class, 'updateOperator']);
        Route::delete('operators/{operator}', [ProductionConfigurationController::class, 'destroyOperator']);

        Route::get('flute-factors', [ProductionConfigurationController::class, 'fluteFactors']);
        Route::post('flute-factors', [ProductionConfigurationController::class, 'storeFluteFactor']);
        Route::put('flute-factors/{fluteFactor}', [ProductionConfigurationController::class, 'updateFluteFactor']);
        Route::delete('flute-factors/{fluteFactor}', [ProductionConfigurationController::class, 'destroyFluteFactor']);

        Route::get('optimization-rules', [ProductionConfigurationController::class, 'optimizationRules']);
        Route::post('optimization-rules', [ProductionConfigurationController::class, 'storeOptimizationRule']);
        Route::put('optimization-rules/{rule}', [ProductionConfigurationController::class, 'updateOptimizationRule']);
        Route::delete('optimization-rules/{rule}', [ProductionConfigurationController::class, 'destroyOptimizationRule']);
        Route::post('optimization-rules/apply', [ProductionConfigurationController::class, 'applyOptimizationRules']);
    });
});
