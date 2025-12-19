<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PaperQualityController;
use App\Http\Controllers\ReelReceiptController;
use App\Http\Controllers\ReelIssueController;
use App\Http\Controllers\ReelReturnController;
use App\Http\Controllers\MonthlyConsumptionReportController;
use App\Http\Controllers\ReelStockReportController;
use App\Http\Controllers\ReelReceiptReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyClosingReportController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\AuditController;

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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Users
    Route::apiResource('users', UserController::class);

    // Roles
    Route::get('roles', [RoleController::class, 'index']);

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

    // Reports
    Route::get('reports/monthly-consumption', [MonthlyConsumptionReportController::class, 'index']);
    Route::get('reports/reel-stock', [ReelStockReportController::class, 'index']);
    Route::get('reports/reel-stock/{reel_no}/history', [ReelStockReportController::class, 'getReelHistory']);
    Route::get('reports/reel-receipt', [ReelReceiptReportController::class, 'index']);
    Route::get('reports/monthly-closing', [MonthlyClosingReportController::class, 'index']);
    Route::get('reports/audits', [AuditController::class, 'index']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // User Permissions
    Route::get('user-permissions/{userId}', [UserPermissionController::class, 'getPermissions']);
    Route::post('user-permissions/{userId}', [UserPermissionController::class, 'updatePermissions']);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Carton Sketch
    Route::post('carton-sketch/export-pdf', [CartonSketchController::class, 'exportPdf']);

    // Setup (Admin only)
    Route::prefix('setup')->group(function () {
        Route::get('settings', [SetupController::class, 'getSettings']);
        Route::post('settings', [SetupController::class, 'updateSetting']);
        Route::post('reset-all', [SetupController::class, 'resetAllData']);
        Route::post('delete-table', [SetupController::class, 'deleteTable']);
        Route::get('tables', [SetupController::class, 'getTables']);
        Route::post('upload-logo', [SetupController::class, 'uploadLogo']);
    });
});
