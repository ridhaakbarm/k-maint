<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'K-Maint') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 Bootstrap Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        @font-face {
            font-family: "Poppins";
            src: url("{{ asset('fonts/Poppins/Poppins-Regular.ttf') }}");
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }

        .required:after {
            content: " *";
            color: red;
        }

        /* Loading Modal */
        .loading-modal {
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(8, 253, 110, 0.5);
            display: none;
        }

        .loading-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Navbar & Sidebar Colors */
        .navbar-avian {
            background-color: #1e7e34 !important;
        }
        
        .sidebar-dark-avian {
            background-color: #343a40;
        }
        
        .brand-link.navbar-avian {
            background-color: #1e7e34;
        }

        /* Sidebar Improvements */
        .nav-sidebar .nav-link p {
            white-space: normal;
            display: inline-block;
            margin-bottom: 0;
            vertical-align: middle;
            width: calc(100% - 30px);
        }

        .nav-sidebar .nav-link i {
            width: 25px;
            text-align: center;
        }

        .nav-header {
            padding: 1.5rem 1rem .5rem !important;
            background-color: rgba(255,255,255,.05);
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #adb5bd !important;
            font-weight: 600;
        }

        /* Sidebar Scrollable */
        .main-sidebar {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .main-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .main-sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        .main-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }

        .main-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Icon Consistency */
        .nav-sidebar .nav-icon {
            margin-right: .5rem;
            font-size: 1.1rem;
        }

        /* Remove Blue Border on Focus */
        .nav-link:focus {
            box-shadow: none !important;
            outline: none;
        }

        /* Active Menu State */
        .nav-sidebar .nav-link.active {
            background-color: rgba(255,255,255,.1);
            color: #fff;
        }

        .nav-sidebar .nav-treeview .nav-link.active {
            background-color: rgba(255,255,255,.05);
        }

        /* Dropdown Menu Improvements */
        .dropdown-menu {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        /* Badge Styling */
        .badge {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
            border-radius: 10px;
        }

        /* Modal Improvements */
        .modal-header.bg-success {
            background-color: #1e7e34 !important;
        }

        /* Content Wrapper */
        .content-wrapper {
            min-height: calc(100vh - 57px);
            background-color: #f4f6f9;
            transition: all 0.3s ease;
        }

        /* Footer */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        /* Brand Image */
        .brand-image {
            max-height: 33px;
            width: auto;
        }

        .img-white {
            filter: brightness(0) invert(1);
        }

        /* ========== MOBILE SIDEBAR STYLES ========== */
        @media (max-width: 768px) {
            /* Wrapper positioning */
            .wrapper {
                position: relative;
                overflow-x: hidden;
            }
            
            /* Sidebar styling for mobile */
            .main-sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100%;
                z-index: 1050;
                transition: left 0.3s ease-in-out;
                box-shadow: none;
            }
            
            /* When sidebar is open */
            body.sidebar-open .main-sidebar {
                left: 0;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            }
            
            /* Content wrapper margin adjustment */
            body.sidebar-open .content-wrapper,
            body.sidebar-open .main-footer {
                transform: translateX(280px);
            }
            
            /* Navbar fixed on mobile */
            .main-header {
                position: fixed;
                width: 100%;
                top: 0;
                z-index: 1040;
            }
            
            /* Add top padding to content to account for fixed navbar */
            .content-wrapper {
                margin-top: 57px;
                padding-top: 10px;
            }
            
            /* Prevent body scroll when sidebar is open */
            body.sidebar-open {
                overflow: hidden;
            }
            
            /* Better touch targets for mobile */
            .nav-sidebar .nav-link {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .nav-sidebar .nav-link i {
                font-size: 1.2rem;
                margin-right: 12px;
            }
            
            /* Adjust brand link for mobile */
            .brand-link {
                padding: 15px;
                text-align: center;
            }
            
            /* Make treeview easier to tap */
            .nav-treeview .nav-link {
                padding-left: 45px !important;
            }
        }
        
        /* Sidebar Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1045;
            display: none;
            cursor: pointer;
        }
        
        body.sidebar-open .sidebar-overlay {
            display: block;
        }
        
        /* Desktop styles - sidebar visible by default */
        @media (min-width: 769px) {
            .main-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                height: 100%;
                z-index: 1030;
            }
            
            .content-wrapper,
            .main-footer {
                margin-left: 280px;
                transition: margin-left 0.3s ease;
            }
            
            body.sidebar-collapse .main-sidebar {
                width: 70px;
            }
            
            body.sidebar-collapse .main-sidebar .brand-text,
            body.sidebar-collapse .main-sidebar .nav-link p {
                display: none;
            }
            
            body.sidebar-collapse .main-sidebar .nav-link i {
                margin-right: 0;
            }
            
            body.sidebar-collapse .content-wrapper,
            body.sidebar-collapse .main-footer {
                margin-left: 70px;
            }
            
            body.sidebar-collapse .main-sidebar .nav-header {
                text-align: center;
                font-size: 10px;
                padding: 15px 5px !important;
            }
        }
    </style>
    
    <link href="{{ asset('css/app.css') }}?date={{ date('Ymd') }}" rel="stylesheet">
    <link href="{{ asset('css/vendor.css') }}?date={{ date('Ymd') }}" rel="stylesheet">
    
    @yield('css')
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed avian">
    <div id="app" class="wrapper">
        <!-- Sidebar Overlay (for mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark navbar-avian">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" id="sidebarToggleBtn" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <!-- Notifications -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('notifications.index') }}">
                        <i class="fas fa-bell"></i> Notifikasi
                        @php
                            $unreadCount = Auth::user()->unreadNotificationsCount();
                        @endphp
                        @if($unreadCount > 0)
                        <span class="badge bg-danger ms-1" id="notificationBadgeSidebar">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ asset('images/default-avatar.png') }}" class="img-circle elevation-1 ml-2"
                            style="width: 26px; height: 26px;" alt="User Avatar">
                        {{ Auth::user()->name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <span class="dropdown-item-text">
                            <small>{{ Auth::user()->department }} • {{ ucfirst(Auth::user()->role) }}</small>
                        </span>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <aside id="sidebar" class="main-sidebar elevation-4 sidebar-dark-avian">
            <a href="/" class="brand-link navbar-avian text-center">
                <img src="{{ asset('images/avian-logo-icon.png') }}" alt="Logo" class="brand-image img-white">
                <span class="brand-text font-weight-light">{{ config('app.name', 'E-Tiket GA') }}</span>
            </a>

            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-flat" data-widget="treeview" role="menu">
                        
                        {{-- 1. DASHBOARD: Hanya untuk Admin kawan --}}
                        @if(Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                                <i class="fas fa-home nav-icon"></i>
                                <p>Dashboard Monitoring</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tickets.monitoring') }}" class="nav-link {{ Route::is('tickets.monitoring') ? 'active' : '' }}">
                                <i class="fas fa-desktop nav-icon"></i>
                                <p>Monitoring Tiket</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('monitoring.team') }}" class="nav-link {{ Route::is('monitoring.team') ? 'active' : '' }}">
                                <i class="fas fa-users-cog nav-icon text-info"></i>
                                <p>Monitoring Tim Teknisi</p>
                            </a>
                        </li>
                        @endif

                        {{-- 2. TECHNICIAN PANEL: Admin & MTC --}}
                        @if(Auth::user()->isAdmin() || Auth::user()->isMTC())
                        <li class="nav-header text-uppercase">Technician Panel</li>
                        <li class="nav-item">
                            <a href="{{ route('teknisi.dashboard') }}" class="nav-link {{ Route::is('teknisi.dashboard') ? 'active' : '' }}">
                                <i class="fas fa-user-clock nav-icon text-warning"></i>
                                <p>Input Aktivitas Harian</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="http://192.168.2.100/kasakata-cmb/public/machine-tracking" target="_blank" class="nav-link">
                                <i class="nav-icon fa-solid fa-tv"></i>
                                <p>Machine Tracking (TV)</p>
                            </a>
                        </li>
                        @endif

                        {{-- 3. MAINTENANCE TICKETS: Semua Role (Admin, MTC, User) --}}
                        <li class="nav-header text-uppercase">Maintenance Tickets</li>
                        <li class="nav-item">
                            <a href="{{ route('tickets.index') }}" class="nav-link {{ Route::is('tickets.index') ? 'active' : '' }}">
                                <i class="fas fa-ticket-alt nav-icon"></i>
                                <p>List Tickets</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tickets.create') }}" class="nav-link {{ Route::is('tickets.create') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle nav-icon"></i>
                                <p>Buat Ticket (Breakdown)</p>
                            </a>
                        </li>
                        @php
                            $dept = strtolower(trim((string) Auth::user()->department));
                            $canAccessInternalTickets = Auth::user()->isAdmin() || Auth::user()->isMTC() || in_array($dept, ['maintenance', 'engineering', 'mtc']);
                        @endphp
                        @if($canAccessInternalTickets)
                        <li class="nav-item">
                            <a href="{{ route('internal-tickets.index') }}" class="nav-link {{ Route::is('internal-tickets.*') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-check nav-icon text-info"></i>
                                <p>Tiket Internal</p>
                            </a>
                        </li>
                        @endif

                        {{-- 4. PREVENTIVE MAINTENANCE: Admin, MTC & Management --}}
                        @if(Auth::user()->isAdmin() || Auth::user()->isMTC() || Auth::user()->isManager() || Auth::user()->isSPV())
                        <li class="nav-header text-uppercase">Preventive Maintenance</li>
                        <li class="nav-item">
                            <a href="{{ route('monitoring.pm') }}" class="nav-link {{ Route::is('monitoring.pm') ? 'active' : '' }}">
                                <i class="fas fa-chart-pie nav-icon"></i>
                                <p>Monitoring PM</p>
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin() || Auth::user()->isMTC())
                        <li class="nav-item">
                            <a href="{{ route('pm.schedule.index') }}" class="nav-link {{ Route::is('pm.schedule.*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check nav-icon"></i>
                                <p>Penjadwalan PM</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pm.execution.index') }}" class="nav-link {{ Route::is('pm.execution.*') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-list nav-icon"></i>
                                <p>Eksekusi PM (Checklist)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.pm.follow-up') }}" 
                               class="nav-link {{ request()->routeIs('reports.pm.follow-up') ? 'active' : '' }}">        
                                <i class="fas fa-tools nav-icon text-warning"></i>
                                <p>Tindakan Selanjutnya</p>
                            </a>
                        </li>
                        @endif
                        @endif

                        {{-- 5. DATA MASTER & SETTINGS: Hanya Admin kawan --}}
                        @if(Auth::user()->isAdmin())
                        <li class="nav-header text-uppercase">Data Master & Settings</li>
                        <li class="nav-item {{ Route::is('users.*') || Route::is('assets.*') || Route::is('machine_parts.*') || Route::is('pm.templates.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Route::is('users.*') || Route::is('assets.*') || Route::is('machine_parts.*') || Route::is('pm.templates.*') ? 'active' : '' }}">
                                <i class="fas fa-database nav-icon"></i>
                                <p>Management Data <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('assets.index') }}" class="nav-link {{ Route::is('assets.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i><p>Data Mesin (Aset)</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('machine_parts.index') }}" class="nav-link {{ Route::is('machine_parts.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i><p>Bagian Mesin</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pm.templates.index') }}" class="nav-link {{ Route::is('pm.templates.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i><p>Template Checklist</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('users.index') }}" class="nav-link {{ Route::is('users.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i><p>Manajemen User</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{-- 6. REPORTING: Hanya Admin kawan --}}
                        <li class="nav-header text-uppercase">Reporting</li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-file-excel nav-icon"></i>
                                <p>Export Laporan</p>
                            </a>
                        </li>
                        @endif
                        
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content">
                <main class="py-4">
                    @yield('content')
                </main>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; {{ date('Y') }} <a href="">Kasakata Kimia</a>.</strong> All rights reserved.
        </footer>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="fas fa-file-excel me-2"></i>Export Laporan ke Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs mb-4" id="exportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ticket-tab" data-bs-toggle="tab" data-bs-target="#ticket-export" type="button" role="tab">
                                <i class="fas fa-ticket-alt me-2"></i>Export Tiket
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pm-tab" data-bs-toggle="tab" data-bs-target="#pm-export" type="button" role="tab">
                                <i class="fas fa-clipboard-check me-2"></i>Export PM
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="exportTabContent">
                        <!-- TICKET EXPORT TAB -->
                        <div class="tab-pane fade show active" id="ticket-export" role="tabpanel">
                            <form action="{{ route('export.excel') }}" method="GET" id="exportTicketForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="export_status" class="form-label fw-bold">Status</label>
                                        <select class="form-select" id="export_status" name="status">
                                            <option value="all">Semua Status</option>
                                            <option value="open">Open</option>
                                            <option value="onprogress">On Progress</option>
                                            <option value="schedule">Schedule</option>
                                            <option value="request_to_close">Request to Close</option>
                                            <option value="closed">Closed</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="export_category" class="form-label fw-bold">Kategori</label>
                                        <select class="form-select" id="export_category" name="category_id">
                                            <option value="all">Semua Kategori</option>
                                        </select>
                                        <small class="text-muted">Filter kategori opsional</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label fw-bold">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label fw-bold">Tanggal Akhir</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date">
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Tips:</strong> Kosongkan filter untuk mengexport semua data tiket.
                                </div>

                                <div class="modal-footer border-0 px-0 pb-0">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-file-excel me-1"></i>Export Tiket ke Excel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- PM EXPORT TAB -->
                        <div class="tab-pane fade" id="pm-export" role="tabpanel">
                            <form action="{{ route('export.pm') }}" method="GET" id="exportPmForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="pm_start_date" class="form-label fw-bold">
                                            <i class="fas fa-calendar-alt me-2"></i>Tanggal Mulai
                                        </label>
                                        <input type="date" class="form-control" id="pm_start_date" name="start_date" value="{{ now()->startOfMonth()->toDateString() }}">
                                        <small class="text-muted">Awal periode data PM yang ingin diexport</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="pm_end_date" class="form-label fw-bold">
                                            <i class="fas fa-calendar-check me-2"></i>Tanggal Akhir
                                        </label>
                                        <input type="date" class="form-control" id="pm_end_date" name="end_date" value="{{ now()->endOfMonth()->toDateString() }}">
                                        <small class="text-muted">Akhir periode data PM yang ingin diexport</small>
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-secondary mb-0">
                                            <h6 class="fw-bold mb-2">
                                                <i class="fas fa-info-circle text-primary me-2"></i>
                                                Informasi Export PM
                                            </h6>
                                            <ul class="mb-0 small">
                                                <li>Data yang diexport mengikuti rentang tanggal cek atau due date PM.</li>
                                                <li>Format Excel dibuat 1 baris per item checklist agar detail bisa difilter per kolom.</li>
                                                <li>Kolom FA-Code tidak disertakan di export PM.</li>
                                                <li>Status PM yang akan diexport: Semua status (In Progress, Completed, Verified)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer border-0 px-0 pb-0">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-file-excel me-1"></i>Export PM ke Excel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts - Load in Correct Order -->
    <!-- jQuery First -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 & Moment.js -->
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    
    <!-- Custom Scripts -->
    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ========== SIDEBAR TOGGLE FUNCTIONALITY ==========
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const body = document.body;
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const isMobile = () => window.innerWidth <= 768;
            
            // Function to toggle sidebar
            function toggleSidebar() {
                if (isMobile()) {
                    // Mobile: toggle sidebar-open class
                    body.classList.toggle('sidebar-open');
                } else {
                    // Desktop: toggle sidebar-collapse class
                    body.classList.toggle('sidebar-collapse');
                }
            }
            
            // Function to close sidebar (mobile only)
            function closeSidebar() {
                if (isMobile()) {
                    body.classList.remove('sidebar-open');
                }
            }
            
            // Toggle button click event
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }
            
            // Close sidebar when clicking overlay (mobile only)
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    closeSidebar();
                });
            }
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    // If switching from mobile to desktop
                    if (!isMobile() && body.classList.contains('sidebar-open')) {
                        body.classList.remove('sidebar-open');
                    }
                    
                    // If switching from desktop to mobile and sidebar is collapsed
                    if (isMobile() && body.classList.contains('sidebar-collapse')) {
                        body.classList.remove('sidebar-collapse');
                    }
                }, 250);
            });
            
            // Close sidebar when clicking on a link (mobile only - optional)
            const navLinks = document.querySelectorAll('.nav-sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (isMobile() && !this.getAttribute('data-bs-toggle')) {
                        // Small delay to allow navigation
                        setTimeout(() => {
                            closeSidebar();
                        }, 150);
                    }
                });
            });
            
            // ========== EXISTING FUNCTIONALITY ==========
            
            // Initialize Select2 if present
            if(typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }

            // Update Notification Count
            function updateNotificationCount() {
                $.ajax({
                    url: '{{ url("/api/notifications/unread-count") }}',
                    method: 'GET',
                    success: function(data) {
                        const badge = $('#notificationBadgeSidebar');
                        if(data.count > 0) {
                            badge.text(data.count > 99 ? '99+' : data.count).show();
                        } else {
                            badge.hide();
                        }
                    },
                    error: function() {
                        console.log('Failed to fetch notification count');
                    }
                });
            }

            // Initial load and periodic update
            updateNotificationCount();
            setInterval(updateNotificationCount, 60000); // Update every 60 seconds

            // Treeview Menu Toggle
            const treeviewLinks = document.querySelectorAll('[data-widget="treeview"] .nav-link');
            treeviewLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const parentLi = this.closest('.nav-item');
                    if(parentLi && parentLi.querySelector('.nav-treeview')) {
                        e.preventDefault();
                        if(parentLi.classList.contains('menu-open')) {
                            parentLi.classList.remove('menu-open');
                        } else {
                            // Close other open menus
                            document.querySelectorAll('.nav-item.menu-open').forEach(item => {
                                if(item !== parentLi) {
                                    item.classList.remove('menu-open');
                                }
                            });
                            parentLi.classList.add('menu-open');
                        }
                    }
                });
            });
        });
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
