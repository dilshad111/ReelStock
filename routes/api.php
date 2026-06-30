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
use App\Http\Controllers\SampleSubmissionController;
use App\Http\Controllers\FGDamageController;

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

// Public Auth routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Admin only Auth routes
    Route::post('/register', [AuthController::class, 'register'])->middleware('check.admin');
    
    // Normal Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);

    // Suppliers
    Route::middleware('check.permission:suppliers')->group(function () {
        Route::apiResource('suppliers', SupplierController::class);
    });

    // Users (admin only via check.admin or check.permission:users)
    Route::middleware('check.permission:users')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('roles', [RoleController::class, 'index']);
    });

    // Paper Colors & UOMs
    Route::middleware('check.permission:paper-colors')->group(function () {
        Route::apiResource('paper-colors', PaperColorController::class);
    });
    Route::middleware('check.permission:unit-of-measures')->group(function () {
        Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
    });

    // Paper Qualities
    Route::middleware('check.permission:qualities')->group(function () {
        Route::apiResource('paper-qualities', PaperQualityController::class);
    });

    // Reel Receipts
    Route::middleware('check.permission:receipts')->group(function () {
        Route::apiResource('reel-receipts', ReelReceiptController::class);
        Route::post('reel-receipts/bulk', [ReelReceiptController::class, 'bulkStore']);
    });

    // Reel Issues
    Route::middleware('check.permission:issues')->group(function () {
        Route::apiResource('reel-issues', ReelIssueController::class);
        Route::get('fetch-reel/{reel_no}', [ReelIssueController::class, 'fetchReel']);
    });

    // Reel Returns
    Route::middleware('check.permission:return-supplier')->group(function () {
        Route::apiResource('reel-returns', ReelReturnController::class);
        Route::get('fetch-reel-return/{reel_no}', [ReelReturnController::class, 'fetchReel']);
    });

    // Reel Location Transfers
    Route::middleware('check.permission:reel-transfer')->group(function () {
        Route::get('reel-transfers/fetch-reel/{reel_no}', [ReelTransferController::class, 'fetchReel']);
        Route::apiResource('reel-transfers', ReelTransferController::class)->only(['index', 'store']);
    });

    // Reports
    Route::middleware('check.permission:monthly-consumption')->group(function () {
        Route::get('reports/monthly-consumption', [MonthlyConsumptionReportController::class, 'index']);
    });
    Route::middleware('check.permission:reel-stock')->group(function () {
        Route::get('reports/reel-stock', [ReelStockReportController::class, 'index']);
        Route::get('reports/reel-stock/sizes', [ReelStockReportController::class, 'getAvailableSizes']);
        Route::get('reports/reel-stock/qualities', [ReelStockReportController::class, 'getAvailableQualities']);
        Route::get('reports/reel-stock/suppliers', [ReelStockReportController::class, 'getAvailableSuppliers']);
        Route::get('reports/reel-stock/{reel_no}/history', [ReelStockReportController::class, 'getReelHistory']);
    });
    Route::middleware('check.permission:reel-stock-count')->group(function () {
        Route::get('reports/reel-stock-count', [ReelStockReportController::class, 'getReelStockCount']);
    });
    Route::middleware('check.permission:reel-receipt')->group(function () {
        Route::get('reports/reel-receipt', [ReelReceiptReportController::class, 'index']);
    });
    Route::middleware('check.permission:monthly-closing')->group(function () {
        Route::get('reports/monthly-closing', [MonthlyClosingReportController::class, 'index']);
    });
    Route::middleware('check.permission:audit-log')->group(function () {
        Route::get('reports/audits', [AuditController::class, 'index']);
    });
    Route::middleware('check.permission:usage-intelligence')->group(function () {
        Route::get('reports/usage-intelligence', [ReelUsageReportController::class, 'usageIntelligence']);
        Route::get('reports/predictive-analytics', [ReelUsageReportController::class, 'predictiveAnalytics']);
    });
    Route::middleware('check.permission:old-reels')->group(function () {
        Route::get('reports/old-reels', [OldReelsReportController::class, 'index']);
    });

    // Dashboards
    Route::middleware('check.permission:dashboard')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
    });
    Route::middleware('check.permission:management-dashboard')->group(function () {
        Route::get('management-dashboard', [DashboardController::class, 'managementIndex']);
    });
    Route::middleware('check.permission:transport-dashboard')->group(function () {
        Route::get('transport-dashboard', [DashboardController::class, 'transportIndex']);
    });

    // User Permissions
    Route::get('user-permissions/{userId}', [UserPermissionController::class, 'getPermissions']);
    Route::middleware('check.admin')->group(function () {
        Route::post('user-permissions/{userId}', [UserPermissionController::class, 'updatePermissions']);
    });

    // Setup
    Route::prefix('setup')->group(function () {
        Route::get('settings', [SetupController::class, 'getSettings']);
        
        Route::middleware('check.admin')->group(function () {
            Route::post('settings', [SetupController::class, 'updateSetting']);
            Route::post('reset-all', [SetupController::class, 'resetAllData']);
            Route::post('delete-table', [SetupController::class, 'deleteTable']);
            Route::get('tables', [SetupController::class, 'getTables']);
            Route::post('upload-logo', [SetupController::class, 'uploadLogo']);
        });
    });

    // Stock Alerts
    Route::middleware('check.permission:stock-alerts')->group(function () {
        Route::get('stock-alerts', [StockAlertController::class, 'index']);
        Route::post('stock-alerts', [StockAlertController::class, 'store']);
        Route::put('stock-alerts/{id}', [StockAlertController::class, 'update']);
        Route::delete('stock-alerts/{id}', [StockAlertController::class, 'destroy']);
        Route::post('stock-alerts/{id}/toggle', [StockAlertController::class, 'toggle']);
        Route::get('stock-alerts/triggered', [StockAlertController::class, 'getTriggeredAlerts']);
    });

    // Reconciliation
    Route::middleware('check.permission:reconciliation')->group(function () {
        Route::post('reconciliation/run', [ReconciliationController::class, 'runFullReconciliation']);
        Route::get('reconciliation/history', [ReconciliationController::class, 'getReconciliationHistory']);
        Route::get('reconciliation/discrepancies', [ReconciliationController::class, 'getDiscrepancies']);
        Route::post('reconciliation/reel/{id}', [ReconciliationController::class, 'reconcileSpecificReel']);
    });

    // Transport Module - Customers
    Route::middleware('check.permission:customers')->group(function () {
        Route::apiResource('customers', CustomerController::class);
        Route::post('customers/{customerId}/addresses', [CustomerController::class, 'storeAddress']);
        Route::put('customers/addresses/{addressId}', [CustomerController::class, 'updateAddress']);
        Route::delete('customers/addresses/{addressId}', [CustomerController::class, 'destroyAddress']);
    });

    // Transport Module - Transporters, Vehicles & Types
    Route::middleware('check.permission:transporters')->group(function () {
        Route::apiResource('transporters', TransporterController::class);
    });
    Route::middleware('check.permission:vehicles')->group(function () {
        Route::apiResource('vehicles', VehicleController::class);
    });
    Route::middleware('check.permission:vehicle-types')->group(function () {
        Route::apiResource('vehicle-types', VehicleTypeController::class);
    });

    // Transport Module - Cartage Rates
    Route::middleware('check.permission:cartage-rates')->group(function () {
        Route::get('cartage-rates', [CartageRateController::class, 'index']);
        Route::post('cartage-rates', [CartageRateController::class, 'store']);
        Route::get('cartage-rates/classifications', [CartageRateController::class, 'getClassifications']);
        Route::post('cartage-rates/bulk-update', [CartageRateController::class, 'bulkUpdate']);
        Route::get('cartage-rates/increment-history', [CartageRateController::class, 'getIncrementHistory']);
        Route::delete('cartage-rates/increment-history/{id}', [CartageRateController::class, 'destroyIncrementHistory']);
        Route::get('cartage-rates/fetch', [CartageRateController::class, 'getRate']);
        Route::delete('cartage-rates/{id}', [CartageRateController::class, 'destroy']);
    });

    // Transport Module - Cartage Reports
    Route::middleware('check.permission:cartage-report')->group(function () {
        Route::get('reports/cartage', [CartageReportController::class, 'index']);
        Route::get('reports/cartage/filters', [CartageReportController::class, 'getFilters']);
    });
    Route::middleware('check.permission:fuel-cost-report')->group(function () {
        Route::get('reports/cartage/fuel-cost', [CartageReportController::class, 'fuelCost']);
    });

    // Transport Module - Cartage Bills & Approval
    Route::middleware('check.permission:cartage')->group(function () {
        Route::get('cartage-bills/pending-count', [CartageBillController::class, 'getPendingCount']);
        Route::get('cartage-bills/next-id', [CartageBillController::class, 'getNextId']);
        Route::apiResource('cartage-bills', CartageBillController::class);
    });
    Route::middleware('check.permission:approve_cartage')->group(function () {
        Route::post('cartage-bills/{id}/approve', [CartageBillController::class, 'approve']);
        Route::post('cartage-bills/{id}/unapprove', [CartageBillController::class, 'unapprove']);
    });

    // Finished Goods Module - Products
    Route::middleware('check.permission:fg-products')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::get('products/by-customer/{customerId}', [ProductController::class, 'byCustomer']);
        Route::get('products/customer-items/{customerId}', [ProductController::class, 'customerItems']);
    });

    // Finished Goods Module - Receipts
    Route::middleware('check.permission:fg-receipts')->group(function () {
        Route::post('fg-receipts/{id}/reverse', [FGReceiptController::class, 'reverse']);
        Route::apiResource('fg-receipts', FGReceiptController::class);
    });

    // Finished Goods Module - Dispatches
    Route::middleware('check.permission:fg-dispatches')->group(function () {
        Route::post('fg-dispatches/{id}/reverse', [FGDispatchController::class, 'reverse']);
        Route::apiResource('fg-dispatches', FGDispatchController::class);
        Route::get('fg-dispatches/job-movement/detail', [FGDispatchController::class, 'getJobMovementDetail']);
        Route::get('fg-dispatches/available-stock/{productId}', [FGDispatchController::class, 'getAvailableStock']);
        Route::get('fg-dispatches/job-details/{jobNumber}', [FGDispatchController::class, 'getJobDetails']);
        Route::get('fg-dispatches/product-details/{productId}', [FGDispatchController::class, 'getProductDetails']);
    });

    // Finished Goods Module - Damages
    Route::middleware('check.permission:fg-damages')->group(function () {
        Route::post('fg-damages/{id}/reverse', [FGDamageController::class, 'reverse']);
        Route::get('fg-damages/available-stock/{productId}/{warehouseId}', [FGDamageController::class, 'getWarehouseStock']);
        Route::apiResource('fg-damages', FGDamageController::class);
    });


    // Finished Goods Module - Reports
    Route::get('fg-reports/filters', [FGReportController::class, 'getFilters']);

    Route::middleware('check.permission:fg-reports|fg-inventory-email')->group(function () {
        Route::get('fg-reports/job', [FGReportController::class, 'jobReport']);
        Route::get('fg-reports/job-detail', [FGReportController::class, 'jobDetail']);
    });

    Route::middleware('check.permission:fg-reports')->group(function () {
        Route::get('fg-reports/reconciliation/check', [FGReportController::class, 'checkReconciliation']);
        Route::post('fg-reports/reconciliation/rebuild', [FGReportController::class, 'rebuildCache']);
        Route::get('fg-reports/stock', [FGReportController::class, 'stockReport']);
        Route::get('fg-reports/audit', [FGReportController::class, 'auditReport']);
    });
    Route::middleware('check.permission:fg-inventory-email')->group(function () {
        Route::get('fg-reports/inventory-email', [FGReportController::class, 'inventoryEmailReport']);
    });
    Route::middleware('check.permission:fg-dashboard')->group(function () {
        Route::get('fg-dashboard', [FGReportController::class, 'dashboard']);
    });

    // QC Inspection Module
    Route::middleware('check.permission:qc-inspection')->group(function () {
        Route::get('qc-inspections/open-lots', [QcInspectionController::class, 'openLots']);
        Route::get('qc-inspections/lot-details/{lotNumber}', [QcInspectionController::class, 'getLotDetails']);
        Route::get('qc-inspections/{id}/report', [QcInspectionController::class, 'report']);
        Route::get('qc-inspections/{id}/report-pdf', [QcInspectionController::class, 'reportPdf']);
        Route::apiResource('qc-inspections', QcInspectionController::class);
    });

    // Raw Material Inventory Module - Categories
    Route::middleware('check.permission:rm-categories')->group(function () {
        Route::apiResource('rm-categories', RMCategoryController::class);
        Route::get('rm-subcategories', [RMCategoryController::class, 'subcategories']);
        Route::post('rm-subcategories', [RMCategoryController::class, 'storeSubcategory']);
        Route::put('rm-subcategories/{rmSubcategory}', [RMCategoryController::class, 'updateSubcategory']);
        Route::delete('rm-subcategories/{rmSubcategory}', [RMCategoryController::class, 'destroySubcategory']);
    });

    // Raw Material Inventory Module - Items
    Route::middleware('check.permission:rm-items')->group(function () {
        Route::apiResource('rm-items', RMItemController::class);
        Route::apiResource('rm-item-supplier-rates', RMItemSupplierRateController::class)->except(['show']);
        Route::post('rm-item-supplier-rates/resolve', [RMItemSupplierRateController::class, 'resolve']);
    });

    // Raw Material Inventory Module - Receipts
    Route::middleware('check.permission:rm-receipts')->group(function () {
        Route::get('rm-receipts/next-grn', [RMReceiptController::class, 'nextGrnNo']);
        Route::apiResource('rm-receipts', RMReceiptController::class);
    });

    // Raw Material Inventory Module - Consumptions
    Route::middleware('check.permission:rm-consumptions')->group(function () {
        Route::apiResource('rm-consumptions', RMConsumptionController::class);
    });

    // Raw Material Inventory Module - Dashboard
    Route::middleware('check.permission:rm-dashboard')->group(function () {
        Route::get('rm-dashboard', [RMDashboardController::class, 'index']);
    });
    
    // Raw Material Inventory Module - Reports
    Route::middleware('check.permission:rm-reports')->group(function() {
        Route::prefix('rm-reports')->group(function() {
            Route::get('inventory', [RMReportController::class, 'currentInventory']);
            Route::get('ledger', [RMReportController::class, 'stockLedger']);
            Route::get('receiving', [RMReportController::class, 'receivingReport']);
            Route::get('consumption', [RMReportController::class, 'consumptionReport']);
            Route::get('reorder-requirement', [RMReportController::class, 'reorderRequirement']);
            Route::get('cost-by-category', [RMReportController::class, 'materialCostByCategory']);
            Route::get('consumption-analysis', [RMReportController::class, 'consumptionAnalysis']);
        });
    });

    // Production / Job Card Routes - Product Engineering
    Route::middleware('check.permission:product-engineering')->group(function () {
        Route::get('/product-engineering/lookups', [ProductEngineeringController::class, 'lookups']);
        Route::get('/product-engineering/{productEngineering}/explode', [ProductEngineeringController::class, 'explode']);
        Route::apiResource('/product-engineering', ProductEngineeringController::class);
    });

    // Production / Job Card Routes - Job Cards
    Route::middleware('check.permission:job-cards')->group(function () {
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
    });

    // Production / Job Card Routes - Job Issues
    Route::middleware('check.permission:job-issue')->group(function () {
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
    });

    // Production Configuration Module & Carton Types
    Route::middleware('check.permission:production-configuration')->group(function () {
        Route::get('/carton-types', [CartonTypeController::class, 'index']);
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

    // Sample Submission Module
    Route::middleware('check.permission:sample-submissions')->group(function () {
        Route::apiResource('sample-submissions', SampleSubmissionController::class)
            ->only(['index', 'store', 'show', 'destroy']);
    });
});
