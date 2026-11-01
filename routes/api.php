<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Helpers\ApiResponse;
use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\ProfileController;
use App\Http\Controllers\API\v1\PortfolioController;
use App\Http\Controllers\API\v1\SkillController;
use App\Http\Controllers\API\v1\ProjectController;
use App\Http\Controllers\API\v1\TaskController;
use App\Http\Controllers\API\v1\ScheduleController;
use App\Http\Controllers\API\v1\ReminderController;
use App\Http\Controllers\API\v1\FinanceCategoryController;
use App\Http\Controllers\API\v1\FinanceTransactionController;
use App\Http\Controllers\API\v1\WorkShiftController;
use App\Http\Controllers\API\v1\BlogController;
use App\Http\Controllers\API\v1\ContactController;
use App\Http\Controllers\API\v1\NotificationController;
use App\Http\Controllers\API\v1\GoalController;
use App\Http\Controllers\API\v1\DebtController;
use App\Http\Controllers\API\v1\SubscriptionController;
use App\Http\Controllers\API\v1\AccountController;
use App\Http\Controllers\API\v1\BudgetController;
use App\Http\Controllers\API\v1\SummaryController;


Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check (no auth)
    Route::get('/health', function () {
        return ApiResponse::success([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ], 'API is healthy');
    })->name('health');

    // Auth routes (no auth required)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // Protected routes (Sanctum auth required)
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/user', [AuthController::class, 'me'])->name('user');
        });

        // Profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::post('/', [ProfileController::class, 'store'])->name('store');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        });

        // Portfolios
        Route::prefix('portfolios')->name('portfolios.')->group(function () {
            Route::get('/', [PortfolioController::class, 'index'])->name('index');
            Route::post('/', [PortfolioController::class, 'store'])->name('store');
            Route::get('/{portfolio}', [PortfolioController::class, 'show'])->name('show');
            Route::put('/{portfolio}', [PortfolioController::class, 'update'])->name('update');
            Route::delete('/{portfolio}', [PortfolioController::class, 'destroy'])->name('destroy');
        });

        // Skills
        Route::prefix('skills')->name('skills.')->group(function () {
            Route::get('/', [SkillController::class, 'index'])->name('index');
            Route::post('/', [SkillController::class, 'store'])->name('store');
            Route::get('/{skill}', [SkillController::class, 'show'])->name('show');
            Route::put('/{skill}', [SkillController::class, 'update'])->name('update');
            Route::delete('/{skill}', [SkillController::class, 'destroy'])->name('destroy');
        });

        // Projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        });

        // Tasks
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/', [TaskController::class, 'store'])->name('store');
            Route::get('/{task}', [TaskController::class, 'show'])->name('show');
            Route::put('/{task}', [TaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        });

        // Schedules
        Route::prefix('schedules')->name('schedules.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::post('/', [ScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}', [ScheduleController::class, 'show'])->name('show');
            Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
            Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
        });

        // Reminders
        Route::prefix('reminders')->name('reminders.')->group(function () {
            Route::get('/', [ReminderController::class, 'index'])->name('index');
            Route::post('/', [ReminderController::class, 'store'])->name('store');
            Route::get('/{reminder}', [ReminderController::class, 'show'])->name('show');
            Route::put('/{reminder}', [ReminderController::class, 'update'])->name('update');
            Route::delete('/{reminder}', [ReminderController::class, 'destroy'])->name('destroy');
        });

        // Finance Categories
        Route::prefix('finance/categories')->name('finance.categories.')->group(function () {
            Route::get('/', [FinanceCategoryController::class, 'index'])->name('index');
            Route::post('/', [FinanceCategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [FinanceCategoryController::class, 'show'])->name('show');
            Route::put('/{category}', [FinanceCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [FinanceCategoryController::class, 'destroy'])->name('destroy');
        });

        // Finance Transactions
        Route::prefix('finance/transactions')->name('finance.transactions.')->group(function () {
            Route::get('/', [FinanceTransactionController::class, 'index'])->name('index');
            Route::post('/', [FinanceTransactionController::class, 'store'])->name('store');
            Route::get('/{transaction}', [FinanceTransactionController::class, 'show'])->name('show');
            Route::put('/{transaction}', [FinanceTransactionController::class, 'update'])->name('update');
            Route::delete('/{transaction}', [FinanceTransactionController::class, 'destroy'])->name('destroy');
        });

        // Work Shifts
        Route::prefix('work-shifts')->name('work-shifts.')->group(function () {
            Route::get('/', [WorkShiftController::class, 'index'])->name('index');
            Route::post('/', [WorkShiftController::class, 'store'])->name('store');
            Route::get('/{shift}', [WorkShiftController::class, 'show'])->name('show');
            Route::put('/{shift}', [WorkShiftController::class, 'update'])->name('update');
            Route::delete('/{shift}', [WorkShiftController::class, 'destroy'])->name('destroy');
        });

        // Blogs
        Route::prefix('blogs')->name('blogs.')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::post('/', [BlogController::class, 'store'])->name('store');
            Route::get('/{blog}', [BlogController::class, 'show'])->name('show');
            Route::put('/{blog}', [BlogController::class, 'update'])->name('update');
            Route::delete('/{blog}', [BlogController::class, 'destroy'])->name('destroy');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
            Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        });

        // Goals
        Route::prefix('goals')->name('goals.')->group(function () {
            Route::get('/', [GoalController::class, 'index'])->name('index');
            Route::post('/', [GoalController::class, 'store'])->name('store');
            Route::get('/{goal}', [GoalController::class, 'show'])->name('show');
            Route::put('/{goal}', [GoalController::class, 'update'])->name('update');
            Route::delete('/{goal}', [GoalController::class, 'destroy'])->name('destroy');
        });

        // Debts
        Route::prefix('debts')->name('debts.')->group(function () {
            Route::get('/', [DebtController::class, 'index'])->name('index');
            Route::post('/', [DebtController::class, 'store'])->name('store');
            Route::get('/{debt}', [DebtController::class, 'show'])->name('show');
            Route::put('/{debt}', [DebtController::class, 'update'])->name('update');
            Route::delete('/{debt}', [DebtController::class, 'destroy'])->name('destroy');
        });

        // Subscriptions
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::post('/', [SubscriptionController::class, 'store'])->name('store');
            Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
            Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
            Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
        });

        // Accounts
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::post('/', [AccountController::class, 'store'])->name('store');
            Route::get('/{account}', [AccountController::class, 'show'])->name('show');
            Route::put('/{account}', [AccountController::class, 'update'])->name('update');
            Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('budgets')->name('budgets.')->group(function () {
            Route::get('/', [BudgetController::class, 'index'])->name('index');
            Route::post('/', [BudgetController::class, 'store'])->name('store');
            Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');
            Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
            Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');
        });

        // Summaries
        Route::prefix('summaries')->name('summaries.')->group(function () {
            Route::get('/', [SummaryController::class, 'index'])->name('index');
            Route::post('/', [SummaryController::class, 'store'])->name('store');
            Route::get('/{summary}', [SummaryController::class, 'show'])->name('show');
            Route::put('/{summary}', [SummaryController::class, 'update'])->name('update');
            Route::delete('/{summary}', [SummaryController::class, 'destroy'])->name('destroy');
        });
    });

    // Public routes (no auth) — Contacts
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
    });
});
