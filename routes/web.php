<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\JobIssueController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect('/');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/job-cards/{id}/print', [JobCardController::class, 'print']);
    Route::get('/job-cards/{id}/versions/{versionId}/print', [JobCardController::class, 'printVersion']);
    Route::get('/job-issues/{jobIssue}/print', [JobIssueController::class, 'print']);
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
