<?php

use Illuminate\Support\Facades\Route;
// Import Controller Sistem E-Ticket
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\InternalTicketController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\PicController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\MachinePartController;

// Import Controller Sistem PM (Preventive Maintenance)
use App\Http\Controllers\PmScheduleController;
use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\PmCheckController;
use App\Http\Controllers\SchedulingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Semua Role: Admin, GA, MTC, User)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // Dashboard Utama K-Maint
    Route::get('/dashboard', [TicketController::class, 'dashboard'])->name('dashboard');

    // --- FITUR E-TICKET (BREAKDOWN) ---
Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/monitoring-tiket', [TicketController::class, 'monitoring'])->name('monitoring');
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
    Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
    Route::put('/{ticket}/update-basic', [TicketController::class, 'updateBasic'])->name('updateBasic');
    Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');
    
    // RUTE BARU UNTUK AKSI PENGERJAAN
    Route::post('/{ticket}/start', [TicketController::class, 'startWork'])->name('startWork');
    Route::post('/{ticket}/finish', [TicketController::class, 'markAsFinished'])->name('markAsFinished');
    Route::post('/{ticket}/close', [TicketController::class, 'closeTicket'])->name('closeTicket');
    Route::post('/{ticket}/note', [TicketController::class, 'addNote'])->name('addNote');

    // Rute lama tetap dipertahankan jika masih dibutuhkan
    Route::post('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('updateStatus');
    Route::post('/{ticket}/upload-after-photo', [TicketController::class, 'uploadAfterPhoto'])->name('uploadAfterPhoto');

    // TAMBAHKAN DUA BARIS INI KAWAN:   
    Route::post('/{ticket}/pending', [TicketController::class, 'setPending'])->name('setPending');
    Route::post('/{ticket}/spv-review', [TicketController::class, 'spvReview'])->name('spvReview');
    Route::post('/{ticket}/resume', [TicketController::class, 'resumeWork'])->name('resumeWork');
    Route::post('/{ticket}/reject-by-user', [TicketController::class, 'rejectByUser'])->name('rejectByUser');
    });

    // --- FITUR TIKET INTERNAL (TEMUAN PM / INSTRUKSI LISAN) ---
    Route::prefix('internal-tickets')->name('internal-tickets.')->group(function () {
        Route::get('/', [InternalTicketController::class, 'index'])->name('index');
        Route::get('/create', [InternalTicketController::class, 'create'])->name('create');
        Route::post('/', [InternalTicketController::class, 'store'])->name('store');
        Route::get('/{internalTicket}', [InternalTicketController::class, 'show'])->name('show');
        Route::post('/{internalTicket}/start', [InternalTicketController::class, 'startWork'])->name('startWork');
        Route::post('/{internalTicket}/progress', [InternalTicketController::class, 'updateProgress'])->name('updateProgress');
        Route::post('/{internalTicket}/close', [InternalTicketController::class, 'close'])->name('close');
        Route::post('/{internalTicket}/note', [InternalTicketController::class, 'addNote'])->name('addNote');
        Route::delete('/{internalTicket}', [InternalTicketController::class, 'destroy'])->name('destroy');
    });

    // --- FITUR PREVENTIVE MAINTENANCE (EKSEKUSI) ---
Route::prefix('pm-checks')->group(function () {
    Route::get('/list/{scheduleType?}', [PmCheckController::class, 'index'])->name('pm.execution.index');
    Route::get('/show/{id}', [PmCheckController::class, 'show'])->name('pm.execution.show');
    Route::get('/create/{scheduleId}', [PmCheckController::class, 'create'])->name('pm-checks.create');
    Route::post('/store/{scheduleId}', [PmCheckController::class, 'store'])->name('pm-checks.store');

    // FIX: Ubah nama rute agar sesuai dengan pemanggilan di Blade (index.blade.php)
    Route::post('/{id}/start', [PmCheckController::class, 'startWork'])->name('pm.execution.startWork');

    Route::post('/{checkId}/batch-update-items', [PmCheckController::class, 'batchUpdateItems'])->name('pm.execution.batch-update');
    Route::post('/{id}/complete', [PmCheckController::class, 'complete'])->name('pm-checks.complete');

    // Preview template (read-only mode)
    Route::get('/preview/{scheduleId}', [PmCheckController::class, 'previewTemplate'])->name('pm-checks.preview');

    Route::post('/{id}/verify', [PmCheckController::class, 'verify'])->name('pm.execution.verify');
    Route::post('/{id}/approve', [PmCheckController::class, 'approve'])->name('pm.execution.approve');
});

    // --- FITUR NOTIFIKASI (FIX ERROR NOTDefined) ---
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    });

    // API Search & Global Data (Untuk Select2)
    Route::get('/assets/search', [AssetController::class, 'search'])->name('assets.search');
    Route::get('/search/pics', [PicController::class, 'search_pic'])->name('search.pic');
    Route::get('/search-pic-vendor', [VendorController::class, 'search_vendor'])->name('search.vendor');
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('api.notifications.unread-count');
    Route::get('/pics-list', [PicController::class, 'getPics'])->name('pics.list');

    // Export & Reporting
    Route::get('/export/excel', [ExportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pm', [ExportController::class, 'exportPm'])->name('export.pm');
    Route::get('/export/manager-report', [ExportController::class, 'exportManagerReport'])->name('export.manager-report');
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/pm', [ReportController::class, 'pmIndex'])->name('pm.index');
        Route::get('/pm/show', [ReportController::class, 'pmShow'])->name('pm.show');
        Route::get('/pm/follow-up', [ReportController::class, 'pmFollowUp'])->name('pm.follow-up');
    });
});

/*
|--------------------------------------------------------------------------
| Admin-only Routes (Manajemen Data Master)
|--------------------------------------------------------------------------
*/
// --- BAGIAN ADMIN (DATA MASTER) ---
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('assets', AssetController::class);
    // TAMBAHKAN INI: Rute untuk Import Data Mesin (Asset)
    Route::post('/assets/import', [AssetController::class, 'import'])->name('assets.import');
    Route::resource('users', UserController::class);
    Route::get('/admin/login-settings', [App\Http\Controllers\Admin\LoginSettingController::class, 'edit'])->name('admin.login-settings.edit');
    Route::put('/admin/login-settings', [App\Http\Controllers\Admin\LoginSettingController::class, 'update'])->name('admin.login-settings.update');

    // Manajemen Aset & Komponen (Pusat Data Terpadu)
    Route::resource('assets', AssetController::class);
    Route::resource('machine_parts', MachinePartController::class);
    Route::resource('pics', PicController::class);
    Route::resource('vendors', VendorController::class);

    // MANAJEMEN PREVENTIVE MAINTENANCE (MASTER)
    // Nama rute disesuaikan agar cocok dengan sidebar (pm.schedule dan pm.templates)
    Route::resource('pm-schedules', PmScheduleController::class)->names('pm.schedule');
    Route::post('pm-schedules/{pmSchedule}/toggle-status', [PmScheduleController::class, 'toggleStatus'])->name('pm.schedule.toggle-status');
    
    Route::resource('checklist-templates', ChecklistTemplateController::class)->names('pm.templates');
    Route::post('checklist-templates/import', [ChecklistTemplateController::class, 'import'])->name('pm.templates.import');
    Route::get('checklist-templates/by-schedule/{scheduleId}', [ChecklistTemplateController::class, 'getBySchedule'])->name('pm.templates.by-schedule');
    Route::get('checklist-templates-export', [ChecklistTemplateController::class, 'export'])->name('pm.templates.export');

    // Fitur Penjadwalan (Assignment Teknisi)
    Route::get('/scheduling', [SchedulingController::class, 'index'])->name('scheduling.index');
    Route::post('/scheduling/generate', [SchedulingController::class, 'generate'])->name('scheduling.generate');
    Route::get('/scheduling/get-machines', [SchedulingController::class, 'getMachinesBySchedule'])->name('scheduling.get-machines');

    // Manajemen Kategori/Area lama
    Route::resource('categories', CategoryController::class);
    Route::resource('areas', AreaController::class);
});

// --- DASHBOARD & MONITORING BOARD ---
// Update bagian route Monitoring
Route::middleware(['auth'])->group(function () {
    // Dashboard Utama (Admin Monitoring)
    Route::get('/dashboard', [MonitoringController::class, 'index'])->name('dashboard');

    // Dashboard Input Teknisi
    Route::get('/teknisi/dashboard', [MonitoringController::class, 'technicianDashboard'])->name('teknisi.dashboard');

    // Monitoring Aktivitas Tim (Admin Only)
    Route::get('/monitoring/team', [MonitoringController::class, 'teamMonitoring'])->name('monitoring.team');
    Route::get('/monitoring/team/export', [MonitoringController::class, 'exportTeamMonitoring'])->name('monitoring.team.export');
    Route::get('/monitoring/pm', [MonitoringController::class, 'pmMonitoring'])->name('monitoring.pm');

    // Aksi Monitoring
    Route::post('/monitoring/start', [MonitoringController::class, 'startActivity'])->name('monitoring.start');
    Route::post('/monitoring/stop/{id}', [MonitoringController::class, 'stopActivity'])->name('monitoring.stop');

    Route::post('/monitoring/clock-in', [MonitoringController::class, 'clockIn'])->name('monitoring.clock-in');
    Route::post('/monitoring/clock-out', [MonitoringController::class, 'clockOut'])->name('monitoring.clock-out');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/pm/follow-up', [ReportController::class, 'pmFollowUp'])->name('pm.follow-up');
        Route::post('/pm/update-status/{id}', [ReportController::class, 'updateFollowUpStatus'])->name('pm.update-follow-up-status');
    });
});
