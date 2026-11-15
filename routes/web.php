<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\WorkExperienceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/jobs/search', [JobController::class, 'search'])->name('jobs.search');
Route::resource('jobs', JobController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
Route::resource('jobs', JobController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'register'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('auth');
Route::get('/candidates/{user}', [ProfileController::class, 'show'])->name('profiles.show');

Route::middleware('auth')->group(function () {
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/bookmarks/{job}', [BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/bookmarks/{job}', [BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
    Route::get('/my-applications', [ApplicantController::class, 'index'])->name('applications.index');
    Route::patch('/applications/{applicant}/status', [ApplicantController::class, 'updateStatus'])->name('applications.status.update');
    Route::resource('work-experiences', WorkExperienceController::class)->only(['store', 'update', 'destroy']);
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::patch('/saved-searches/{savedSearch}', [SavedSearchController::class, 'update'])->name('saved-searches.update');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
});

Route::post('/jobs/{job}/apply', [ApplicantController::class, 'store'])->name('applications.store')->middleware('auth');
Route::middleware('auth')->group(function () {
    Route::get('/applications/{applicant}/resume', [ApplicantController::class, 'resume'])->name('applications.resume');
    Route::delete('/applications/{applicant}', [ApplicantController::class, 'destroy'])->name('applications.destroy');
});

Route::get('/geocode', [GeocodeController::class, 'geocode'])->middleware('throttle:30,1');
