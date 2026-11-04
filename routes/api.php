<?php

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\v1\AccountController;
use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\BlogController;
use App\Http\Controllers\API\v1\BudgetController;
use App\Http\Controllers\API\v1\ContactController;
use App\Http\Controllers\API\v1\DebtController;
use App\Http\Controllers\API\v1\FinanceCategoryController;
use App\Http\Controllers\API\v1\FinanceTransactionController;
use App\Http\Controllers\API\v1\GoalController;
use App\Http\Controllers\API\v1\NotificationController;
use App\Http\Controllers\API\v1\PortfolioController;
use App\Http\Controllers\API\v1\ProfileController;
use App\Http\Controllers\API\v1\ProjectController;
use App\Http\Controllers\API\v1\ReminderController;
use App\Http\Controllers\API\v1\ScheduleController;
use App\Http\Controllers\API\v1\SkillController;
use App\Http\Controllers\API\v1\SubscriptionController;
use App\Http\Controllers\API\v1\SummaryController;
use App\Http\Controllers\API\v1\TaskController;
use App\Http\Controllers\API\v1\TelegramPairingController;
use App\Http\Controllers\API\v1\TelegramPairingIntegrationController;
use App\Http\Controllers\API\v1\WorkShiftController;
use App\Http\Controllers\API\v1\InvitationController;
use App\Http\Controllers\API\v1\TelegramBridgeController;
use App\Http\Controllers\API\v1\ScheduleBridgeController;
use App\Http\Controllers\API\v1\ReminderBridgeController;
use App\Http\Controllers\API\v1\ShiftBridgeController;
use App\Http\Controllers\API\v1\ShiftPlanBridgeController;
use App\Http\Controllers\API\v1\ReminderNotifyController;
use App\Http\Controllers\API\v1\FinanceBridgeController;
use App\Http\Controllers\API\v1\CheckinBridgeController;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check (no auth)
    Route::get('/health', function () {
        return ApiResponse::success([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ], 'API is healthy');
    })->name('health');

    // Authentication routes (no auth)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('reset-password');
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

        // Budgets
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

        // Telegram Pairing (Authenticated)
        Route::prefix('auth/telegram')->name('auth.telegram.')->group(function () {
            Route::post('/pairing-code', [TelegramPairingController::class, 'generatePairingCode'])->name('pairing-code');
            Route::get('/status', [TelegramPairingController::class, 'pairingStatus'])->name('status');
            Route::delete('/unlink', [TelegramPairingController::class, 'unlinkTelegram'])->name('unlink');
        });

        // Contacts
        Route::prefix('contacts')->name('contacts.')->middleware('admin')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        });
    });

    // Contacts (Public, no auth required)
    Route::post('/contacts', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('contacts.store');

    // Telegram Pairing (Public, no auth required)
    Route::post('/integrations/telegram/pair', [TelegramPairingIntegrationController::class, 'pair'])
        ->middleware('throttle:10,1')
        ->name('integrations.telegram.pair');

    // Telegram Bridge (Protected, bot auth required)
    Route::prefix('integrations/telegram')
        ->name('integrations.telegram.')
        ->middleware(['telegram.bot', 'throttle:60,1'])
        ->group(function () {
            Route::get('/me', [TelegramBridgeController::class, 'me'])->name('me');

            Route::get('/tasks', [TelegramBridgeController::class, 'tasks'])->name('tasks.index');
            Route::post('/tasks', [TelegramBridgeController::class, 'storeTask'])->name('tasks.store');
            Route::post('/tasks/{task}/complete', [TelegramBridgeController::class, 'completeTask'])
                ->name('tasks.complete');

            Route::get('/schedules', [ScheduleBridgeController::class, 'index'])->name('schedules.index');
            Route::post('/schedules', [ScheduleBridgeController::class, 'store'])->name('schedules.store');

            Route::get('/reminders', [ReminderBridgeController::class, 'index'])->name('reminders.index');
            Route::post('/reminders', [ReminderBridgeController::class, 'store'])->name('reminders.store');

            Route::get('/shifts', [ShiftBridgeController::class, 'index'])->name('shifts.index');
            Route::post('/shifts', [ShiftBridgeController::class, 'store'])->name('shifts.store');

            Route::get('/shift-blocks/preview', [ShiftPlanBridgeController::class, 'previewBlock'])
                ->name('shift-blocks.preview');
            Route::post('/shift-blocks', [ShiftPlanBridgeController::class, 'storeBlock'])
                ->name('shift-blocks.store');

            Route::get('/standby', [ShiftPlanBridgeController::class, 'showStandby'])->name('standby.show');
            Route::post('/standby', [ShiftPlanBridgeController::class, 'storeStandby'])->name('standby.store');


            Route::get('/finance/accounts', [FinanceBridgeController::class, 'accounts'])->name('finance.accounts.index');
            Route::post('/finance/accounts', [FinanceBridgeController::class, 'storeAccount'])->name('finance.accounts.store');
            Route::get('/finance/categories', [FinanceBridgeController::class, 'categories'])->name('finance.categories.index');
            Route::get('/finance/summary', [FinanceBridgeController::class, 'summary'])->name('finance.summary');
            Route::post('/finance/transactions', [FinanceBridgeController::class, 'storeTransaction'])->name('finance.transactions.store');
            Route::get('/finance/budgets', [FinanceBridgeController::class, 'budgets'])->name('finance.budgets.index');
            Route::get('/finance/goals', [FinanceBridgeController::class, 'goals'])->name('finance.goals.index');
            Route::post('/finance/goals/{goal}/contributions', [FinanceBridgeController::class, 'contribute'])->name('finance.goals.contribute');
            Route::post('/finance/accounts/{account}/price', [FinanceBridgeController::class, 'updatePrice'])->name('finance.accounts.price');
        });

    Route::prefix('integrations/telegram/reminders')
        ->name('integrations.telegram.reminders.')
        ->middleware(['telegram.secret', 'throttle:120,1'])
        ->group(function () {
            Route::get('/due', [ReminderNotifyController::class, 'due'])->name('due');
            Route::post('/{reminder}/sent', [ReminderNotifyController::class, 'markSent'])->name('sent');
        });

    // Background job for the bot: secret only
    Route::get('/integrations/telegram/checkins/due', [CheckinBridgeController::class, 'due'])
        ->middleware(['telegram.secret', 'throttle:30,1'])
        ->name('integrations.telegram.checkins.due');

    // Invitation routes (public, no auth required)
    Route::get('/auth/invitations/{token}', [InvitationController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('invitations.show');

    Route::post('/auth/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->middleware('throttle:5,1')
        ->name('invitations.accept');

    // Admin-only invitation management routes (auth required)
    Route::middleware(['auth:sanctum', 'admin'])
        ->prefix('admin/invitations')
        ->name('admin.invitations.')
        ->group(function () {
            Route::get('/', [InvitationController::class, 'index'])->name('index');
            Route::post('/', [InvitationController::class, 'store'])->name('store');
            Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
        });
});
