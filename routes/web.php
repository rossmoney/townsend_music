<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

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

//Route::resource('products', ProductsController::class);

Route::get('/products', [ ProductsController::class, 'index']);
Route::get('/products/{section}', [ ProductsController::class, 'bySection']);

Route::get('/products_original', [ ProductsController::class, 'original']);
Route::get('/products_original/{section}', [ ProductsController::class, 'originalBySection']);