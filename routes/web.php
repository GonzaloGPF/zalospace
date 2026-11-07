<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\SetLocale;
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

// The Spanish routes keep their original, unprefixed URLs (e.g. /about-me)
// to preserve the links already indexed by search engines. English pages
// are served from an equivalent /en/... URL under the "en." route names.
foreach (['es' => ['prefix' => '', 'as' => ''], 'en' => ['prefix' => 'en', 'as' => 'en.']] as $locale => $group) {
    Route::prefix($group['prefix'])->as($group['as'])->middleware(SetLocale::class.":{$locale}")->group(function () {
        Route::get('/', [WelcomeController::class, 'show'])->name('welcome');
        Route::get('/about-me', [InfoController::class, 'aboutMe'])->name('info.about_me');
        Route::get('/projects', [InfoController::class, 'projects'])->name('info.projects');
        Route::get('/work-with-me', [InfoController::class, 'workWithMe'])->name('info.work_with_me');
        Route::get('/my-cv', [InfoController::class, 'cv'])->name('info.cv');
        Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
    });
}

Route::get('/my-cv/pdf/{locale}', [InfoController::class, 'cvPdf'])
    ->whereIn('locale', ['es', 'en'])
    ->name('info.cv_pdf');

Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
