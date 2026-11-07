<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\SitemapController;
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
Route::get('/work-with-me', [InfoController::class, 'workWithMe'])->name('info.work_with_me');
Route::get('/my-cv', [InfoController::class, 'cv'])->name('info.cv');
Route::get('/my-cv/pdf/{locale}', [InfoController::class, 'cvPdf'])
    ->whereIn('locale', ['es', 'en'])
    ->name('info.cv_pdf');

Route::resource('contacts', ContactController::class)->only(['create', 'store']);

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
