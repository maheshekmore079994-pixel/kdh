<?php

use App\Http\Controllers\Site\EnquiryController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (no authentication)
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('site.about');
Route::get('/services', [PageController::class, 'services'])->name('site.services');
Route::get('/contact', [PageController::class, 'contact'])->name('site.contact');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('site.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('site.terms');
Route::post('/enquiry', [EnquiryController::class, 'store'])->middleware('throttle:5,1')->name('site.enquiry');
Route::get('/sitemap.xml', SitemapController::class)->name('site.sitemap');

/*
|--------------------------------------------------------------------------
| Account settings (profile, appearance, security) from the starter kit
|--------------------------------------------------------------------------
*/
require __DIR__.'/settings.php';
