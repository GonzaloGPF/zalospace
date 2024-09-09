<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [WelcomeController::class, 'show'])->name('welcome');
Route::get('/about-me', [InfoController::class, 'aboutMe'])->name('info.about_me');
Route::get('/projects', [InfoController::class, 'projects'])->name('info.projects');
Route::get('/configurator', [InfoController::class, 'configurator'])->name('info.configurator');

Route::resource('contacts', ContactController::class)->only(['create', 'store']);

