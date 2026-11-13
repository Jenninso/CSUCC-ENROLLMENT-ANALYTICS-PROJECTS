<?php

use App\Http\Controllers\AccountSetupController;
use App\Http\Controllers\AtRiskController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CollegeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecisionSupportController;
use App\Http\Controllers\DeleteRequestController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EnrollmentRecordController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RegistrationApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpesApplicationController;
use App\Http\Controllers\StudentRecordController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/home', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:40,1');
Route::get('/login/otp', [AuthController::class, 'otpForm'])->name('login.otp');
Route::post('/login/otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/login/otp/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1')->name('login.otp.resend');
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::get('/register/check', [RegisterController::class, 'check'])->middleware('throttle:30,1')->name('register.check');
Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:8,1');
Route::get('/register/otp', [RegisterController::class, 'otpForm'])->name('register.otp');
Route::post('/register/otp', [RegisterController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/register/otp/resend', [RegisterController::class, 'resendOtp'])->middleware('throttle:5,1')->name('register.otp.resend');
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::get('/forgot-password/lookup', [PasswordResetController::class, 'lookup'])->name('password.lookup');
Route::post('/forgot-password', [PasswordResetController::class, 'identify'])->middleware('throttle:8,1');
Route::get('/forgot-password/questions', [PasswordResetController::class, 'questions'])->name('password.questions');
Route::post('/forgot-password/questions', [PasswordResetController::class, 'verifyQuestions']);
Route::post('/forgot-password/otp/resend', [PasswordResetController::class, 'resendOtp'])->middleware('throttle:5,1')->name('password.otp.resend');
Route::get('/forgot-password/reset', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/spr', [SpesApplicationController::class, 'publicCreate'])->name('spr.public');
Route::post('/spr', [SpesApplicationController::class, 'publicStore'])->middleware('throttle:20,1')->name('spr.public.store');
Route::get('/spr/{token}', [SpesApplicationController::class, 'publicEdit'])->where('token', '[A-Za-z0-9]{40}')->name('spr.public.edit');
Route::put('/spr/{token}', [SpesApplicationController::class, 'publicUpdate'])->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:20,1')->name('spr.public.update');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/setup/password', [AccountSetupController::class, 'passwordForm'])->name('setup.password');
    Route::post('/setup/password', [AccountSetupController::class, 'updatePassword']);
    Route::get('/setup/credentials', [AccountSetupController::class, 'credentialsForm'])->name('setup.credentials');
    Route::post('/setup/credentials', [AccountSetupController::class, 'storeCredentials']);
});

Route::middleware(['auth', 'active', 'setup', 'student.scope'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/executive', [DashboardController::class, 'executive'])->name('executive');

    Route::post('assistant/chat', [AssistantController::class, 'chat'])->middleware('throttle:40,1')->name('assistant.chat');

    Route::get('profile', [SettingsController::class, 'profile'])->name('profile.edit');
    Route::post('profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::get('profile/avatar/{user}', [SettingsController::class, 'avatar'])->name('profile.avatar');

    Route::middleware('permission:enrollment.manage')->group(function () {
        Route::get('students/create', [StudentRecordController::class, 'create'])->name('students.create');
        Route::post('students', [StudentRecordController::class, 'store'])->name('students.store');
        Route::get('students/import', [StudentRecordController::class, 'importForm'])->name('students.import');
        Route::get('students/template', [StudentRecordController::class, 'template'])->name('students.template');
        Route::post('students/import/preview', [StudentRecordController::class, 'importPreview'])->name('students.import.preview');
        Route::post('students/import/confirm', [StudentRecordController::class, 'importCommit'])->name('students.import.confirm');
        Route::get('students/{student}/edit', [StudentRecordController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentRecordController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentRecordController::class, 'destroy'])->name('students.destroy');
    });
    Route::middleware('permission:spr.edit')->group(function () {
        Route::get('spes/{spes}/edit', [SpesApplicationController::class, 'edit'])->name('spes.edit');
        Route::put('spes/{spes}', [SpesApplicationController::class, 'update'])->name('spes.update');
        Route::put('spes/{spes}/status', [SpesApplicationController::class, 'updateStatus'])->name('spes.status');
    });
    Route::middleware('permission:enrollment.manage')->group(function () {
        Route::get('spes/create', [SpesApplicationController::class, 'create'])->name('spes.create');
        Route::post('spes', [SpesApplicationController::class, 'store'])->name('spes.store');
        Route::post('spes/{spes}/student-access', [SpesApplicationController::class, 'allowStudentEdit'])->name('spes.student-access');
        Route::delete('spes/{spes}', [SpesApplicationController::class, 'destroy'])->name('spes.destroy');
    });
    Route::middleware('permission:enrollment.view')->group(function () {
        Route::get('spes', [SpesApplicationController::class, 'index'])->name('spes.index');
        Route::get('spes/share-qr', [SpesApplicationController::class, 'shareQr'])->name('spes.share-qr');
        Route::get('spes/{spes}/print', [SpesApplicationController::class, 'print'])->name('spes.print');
        Route::get('spes/{spes}/pdf', [SpesApplicationController::class, 'pdf'])->name('spes.pdf');
        Route::get('spes/{spes}', [SpesApplicationController::class, 'show'])->name('spes.show');
    });

    Route::middleware('permission:enrollment.manage')->group(function () {
        Route::post('at-risk/warnings', [AtRiskController::class, 'warnings'])->name('at-risk.warnings');
        Route::post('at-risk/{student}/interventions', [AtRiskController::class, 'storeIntervention'])->name('at-risk.interventions.store');
        Route::put('at-risk/interventions/{intervention}', [AtRiskController::class, 'updateIntervention'])->name('at-risk.interventions.update');
    });
    Route::middleware('permission:enrollment.view')->group(function () {
        Route::get('at-risk', [AtRiskController::class, 'index'])->name('at-risk.index');
        Route::get('at-risk/{student}/print', [AtRiskController::class, 'print'])->name('at-risk.print');
        Route::get('at-risk/{student}', [AtRiskController::class, 'show'])->name('at-risk.show');
    });

    Route::middleware('permission:enrollment.view')->group(function () {
        Route::get('students', [StudentRecordController::class, 'index'])->name('students.index');
        Route::get('students/export', [StudentRecordController::class, 'exportCsv'])->name('students.export');
        Route::get('students/{student}', [StudentRecordController::class, 'show'])->name('students.show');
    });

    Route::middleware('permission:enrollment.view')->group(function () {
        Route::get('enrollment', [EnrollmentRecordController::class, 'index'])->name('enrollment.index');
        Route::get('enrollment/export', [EnrollmentRecordController::class, 'exportCsv'])->name('enrollment.export');
        Route::get('enrollment/quality', [EnrollmentRecordController::class, 'quality'])->name('enrollment.quality');
    });
    Route::middleware('permission:enrollment.manage')->group(function () {
        Route::get('enrollment/create', [EnrollmentRecordController::class, 'create'])->name('enrollment.create');
        Route::post('enrollment', [EnrollmentRecordController::class, 'store'])->name('enrollment.store');
        Route::get('enrollment/{enrollment}/edit', [EnrollmentRecordController::class, 'edit'])->name('enrollment.edit');
        Route::put('enrollment/{enrollment}', [EnrollmentRecordController::class, 'update'])->name('enrollment.update');
        Route::delete('enrollment/{enrollment}', [EnrollmentRecordController::class, 'destroy'])->name('enrollment.destroy');
    });

    Route::middleware('permission:analytics.view')->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'summary'])->name('summary');
        Route::get('/trends', [AnalyticsController::class, 'trends'])->name('trends');
        Route::get('/programs', [AnalyticsController::class, 'programAnalytics'])->name('programs');
        Route::get('/colleges', [AnalyticsController::class, 'collegeAnalytics'])->name('colleges');
        Route::get('/retention', [AnalyticsController::class, 'retentionAnalytics'])->name('retention');
    });

    Route::middleware('permission:forecasting.view')->group(function () {
        Route::get('forecasting', [ForecastController::class, 'index'])->name('forecasting.index');
        Route::post('forecasting', [ForecastController::class, 'store'])->name('forecasting.store');
    });

    Route::middleware('permission:ai.view')->group(function () {
        Route::get('ai-support', [DecisionSupportController::class, 'index'])->name('ai-support.index');
        Route::post('ai-support/generate', [DecisionSupportController::class, 'generate'])->name('ai-support.generate');
        Route::get('ai-support/what-if', [DecisionSupportController::class, 'whatIf'])->name('ai-support.what-if');
        Route::post('ai-support/what-if', [DecisionSupportController::class, 'whatIf']);
        Route::get('ai-support/{insight}', [DecisionSupportController::class, 'show'])->name('ai-support.show');
        Route::post('ai-support/{insight}/review', [DecisionSupportController::class, 'review'])->name('ai-support.review');
    });

    Route::middleware('permission:alerts.view')->group(function () {
        Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::post('alerts/generate', [AlertController::class, 'generate'])->name('alerts.generate');
        Route::get('alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
        Route::post('alerts/{alert}/read', [AlertController::class, 'markAsRead'])->name('alerts.mark-read');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports', [ReportController::class, 'store'])->name('reports.generate');
    });

    Route::middleware('permission:data.manage')->prefix('data-management')->name('data-management.')->group(function () {
        Route::resource('academic-years', AcademicYearController::class)->except(['show']);
        Route::resource('semesters', SemesterController::class)->except(['show']);
        Route::resource('colleges', CollegeController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('programs', ProgramController::class)->except(['show']);
    });

    Route::middleware('permission:users.create_admin')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/confirm-delete', [UserController::class, 'confirmDestroy'])->name('users.confirm-delete');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('permission:users.privileges')->group(function () {
        Route::get('users/{user}/privileges', [UserController::class, 'privileges'])->name('users.privileges');
        Route::post('users/{user}/privileges', [UserController::class, 'updatePrivileges'])->name('users.privileges.update');
    });
    Route::middleware('permission:users.approve')->group(function () {
        Route::get('approvals', [RegistrationApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{registration}/approve', [RegistrationApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{registration}/reject', [RegistrationApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('approvals/{registration}/block', [RegistrationApprovalController::class, 'block'])->name('approvals.block');
    });
    Route::middleware('role:Super Administrator')->group(function () {
        Route::get('delete-requests', [DeleteRequestController::class, 'index'])->name('delete-requests.index');
        Route::post('delete-requests/{deleteRequest}/approve', [DeleteRequestController::class, 'approve'])->name('delete-requests.approve');
        Route::post('delete-requests/{deleteRequest}/reject', [DeleteRequestController::class, 'reject'])->name('delete-requests.reject');
        Route::get('audit-logs', [SettingsController::class, 'auditLogs'])->name('audit-logs.index');
        Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail');
    });

    Route::middleware('permission:logs.view')->get('login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::prefix('api')->group(function () {
        Route::get('/enrollment', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'enrollment']);
        Route::get('/enrollment/trends', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'trends']);
        Route::get('/enrollment/programs', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'programs']);
        Route::get('/enrollment/colleges', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'colleges']);
        Route::get('/enrollment/retention', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'retention']);
        Route::get('/analytics/summary', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'summary']);
        Route::match(['get', 'post'], '/forecast', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'forecast']);
        Route::match(['get', 'post'], '/ai/insights', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'insights']);
        Route::get('/alerts', [\App\Http\Controllers\Api\AnalyticsApiController::class, 'alerts']);
    });
});
