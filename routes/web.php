<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - GmatGroup Learning & Study Platform
|--------------------------------------------------------------------------
*/

// Public Portal Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Articles Library & Reader
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/{slug}', [ArticleController::class, 'show'])->name('show');
    Route::post('/{slug}/discussions', [ArticleController::class, 'postDiscussion'])->name('discussions.store');
    Route::post('/{slug}/react', [ArticleController::class, 'toggleReaction'])->name('react');
});

// Topics & Curriculum Map
Route::prefix('topics')->name('topics.')->group(function () {
    Route::get('/', [TopicController::class, 'index'])->name('index');
    Route::get('/{slug}', [TopicController::class, 'show'])->name('show');
});

// Study Groups & Peer Prep
Route::prefix('study-groups')->name('study-groups.')->group(function () {
    Route::get('/', [StudyGroupController::class, 'index'])->name('index');
    Route::post('/', [StudyGroupController::class, 'store'])->name('store');
    Route::get('/{slug}', [StudyGroupController::class, 'show'])->name('show');
    Route::post('/{slug}/join', [StudyGroupController::class, 'join'])->name('join');
    Route::post('/{slug}/discussions', [StudyGroupController::class, 'postDiscussion'])->name('discussions.store');
});

// Member Authentication Routes (Public Student Portal)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Unique Dedicated Admin Authentication Gateway
$adminLoginPath = config('app.admin_login_path', 'secure-admin-login');
Route::get("/{$adminLoginPath}", [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post("/{$adminLoginPath}", [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin-logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// Protected Administration Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});
