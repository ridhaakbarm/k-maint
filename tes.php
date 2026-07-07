<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }

        /* Footer */
        .main-footer {
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
        }

        /* Brand Image */
        .brand-image {
            max-height: 33px;
            width: auto;
        }

        .img-white {
            filter: brightness(0) invert(1);
        }
    </style>
    
    <link href="{{ asset('css/app.css') }}?date={{ date('Ymd') }}" rel="stylesheet">
    <link href="{{ asset('css/vendor.css') }}?date={{ date('Ymd') }}" rel="stylesheet">
    
    @yield('css')
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed avian">
    <div id="app" class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark navbar-avian">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
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
                    <a href="http://192.168.2.100/k-maint/public/dashboard" class="nav-link">
                        <i class="fas fa-user-clock nav-icon text-warning"></i>
                        <p>Dashboard Teknisi</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('teknisi.dashboard') }}" class="nav-link {{ Route::is('teknisi.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-user-clock nav-icon text-warning"></i>
                        <p>Input Aktivitas Harian</p>
                    </a>
                </li>
				<li class="nav-item">
                    <a href="{{ route('tickets.monitoring') }}" class="nav-link {{ Route::is('tickets.monitoring') ? 'active' : '' }}">
                        <i class="fas fa-desktop nav-icon"></i>
                        <p>Monitoring Tiket</p>
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

                {{-- 4. PREVENTIVE MAINTENANCE: Admin & MTC --}}
                @if(Auth::user()->isAdmin() || Auth::user()->isMTC())
                <li class="nav-header text-uppercase">Preventive Maintenance</li>
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
                                        <label for="pm_week" class="form-label fw-bold">
                                            <i class="fas fa-calendar-week me-2"></i>Week Number
                                        </label>
                                        <select class="form-select" id="pm_week" name="week">
                                            <option value="{{ now()->weekOfYear }}" selected>
                                                Week {{ now()->weekOfYear }} (Current Week)
                                            </option>
                                            @for($i = 1; $i <= 52; $i++)
                                                @if($i != now()->weekOfYear)
                                                    <option value="{{ $i }}">Week {{ $i }}</option>
                                                @endif
                                            @endfor
                                        </select>
                                        <small class="text-muted">Pilih week yang ingin diexport</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="pm_schedule_type" class="form-label fw-bold">
                                            <i class="fas fa-clock me-2"></i>Tipe Jadwal
                                        </label>
                                        <select class="form-select" id="pm_schedule_type" name="schedule_type">
                                            <option value="weekly" selected>Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                        </select>
                                        <small class="text-muted">Pilih tipe jadwal PM</small>
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-secondary mb-0">
                                            <h6 class="fw-bold mb-2">
                                                <i class="fas fa-info-circle text-primary me-2"></i>
                                                Informasi Export PM
                                            </h6>
                                            <ul class="mb-0 small">
                                                <li>Data yang diexport: Week, Mesin/Aset, Teknisi, Progress, Detail Checklist</li>
                                                <li>Status PM yang akan diexport: Semua status (In Progress, Completed, Verified)</li>
                                                <li>Detail checklist menampilkan semua item dengan status pengecekan</li>
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
            // Sidebar Toggle
            const sidebarToggle = document.querySelector('[data-widget="pushmenu"]');
            const body = document.querySelector('body');
            
            if(sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    body.classList.toggle('sidebar-collapse');
                });
            }

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

            // Export Modal Filter Preview
            const exportForm = document.getElementById('exportForm');
            if(exportForm) {
                const statusSelect = document.getElementById('export_status');
                const categorySelect = document.getElementById('export_category');
                const startDate = document.getElementById('start_date');
                const endDate = document.getElementById('end_date');
                const filterPreview = document.getElementById('filterPreview');

                function updatePreview() {
                    let preview = [];
                    
                    if(statusSelect.value !== 'all') {
                        preview.push(`<div class="col-md-6"><strong>Status:</strong> ${statusSelect.options[statusSelect.selectedIndex].text}</div>`);
                    }
                    
                    if(categorySelect.value !== 'all') {
                        preview.push(`<div class="col-md-6"><strong>Kategori:</strong> ${categorySelect.options[categorySelect.selectedIndex].text}</div>`);
                    }
                    
                    if(startDate.value) {
                        preview.push(`<div class="col-md-6"><strong>Dari:</strong> ${startDate.value}</div>`);
                    }
                    
                    if(endDate.value) {
                        preview.push(`<div class="col-md-6"><strong>Sampai:</strong> ${endDate.value}</div>`);
                    }
                    
                    if(preview.length === 0) {
                        filterPreview.innerHTML = '<div class="col-12">Pilih filter untuk melihat preview...</div>';
                    } else {
                        filterPreview.innerHTML = preview.join('');
                    }
                }

                statusSelect.addEventListener('change', updatePreview);
                categorySelect.addEventListener('change', updatePreview);
                startDate.addEventListener('change', updatePreview);
                endDate.addEventListener('change', updatePreview);
            }

            // Treeview Menu Toggle
            const treeviewLinks = document.querySelectorAll('[data-widget="treeview"] .nav-link');
            treeviewLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const parentLi = this.closest('.nav-item');
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
                });
            });
        });
    </script>
    @endauth

    @stack('scripts')
</body>
</html>