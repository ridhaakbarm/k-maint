# Export Excel Laporan Efektivitas Teknisi (Comprehensive)

Membuat fitur export Excel yang komprehensif untuk mengukur efektivitas teknisi dari 3 aspek utama: PM (Preventive Maintenance), Tiket Breakdown, dan Tiket Internal + Aktivitas Lainnya. Export ini dirancang dari perspektif **manager** yang butuh data untuk perencanaan dan evaluasi kinerja tim.

## Background & Analisis Sistem Saat Ini

Sistem sudah memiliki 3 export class dasar:
- [TicketsExport.php](file:///c:/laragon/www/K-Maint/app/Exports/TicketsExport.php) — export data tiket mentah (25 kolom)
- [PmExport.php](file:///c:/laragon/www/K-Maint/app/Exports/PmExport.php) — export detail PM check items (31 kolom)
- [TechnicianActivityExport.php](file:///c:/laragon/www/K-Maint/app/Exports/TechnicianActivityExport.php) — monitoring aktivitas teknisi (6 sheets)

**Kekurangan export saat ini:**
1. **PM Export** hanya menampilkan raw data item-per-item, **tidak ada summary per mesin, per teknisi, atau perhitungan durasi pengerjaan**
2. **Ticket Export** tidak menampilkan **durasi pengerjaan, jumlah penjedaan, siapa saja yang mengerjakan, atau distribusi per teknisi**
3. **Tidak ada export untuk Internal Ticket** sama sekali
4. Semua export berdiri sendiri — **tidak ada satu laporan terpadu** yang bisa dibandingkan lintas aspek

---

## Proposed Changes

Kita akan membuat **1 file export baru** bernama `ManagerReportExport.php` yang menghasilkan **Excel multi-sheet** dengan data komprehensif. Export diakses via tombol baru di halaman monitoring.

---

### Component 1: Export Class

#### [NEW] [ManagerReportExport.php](file:///c:/laragon/www/K-Maint/app/Exports/ManagerReportExport.php)

File export utama menggunakan `Maatwebsite\Excel` dengan `WithMultipleSheets`. Berisi **8 sheets**:

---

#### **Sheet 1: Ringkasan Eksekutif (Executive Summary)**
Dashboard angka-angka penting untuk overview cepat.

| Metrik | Sumber Data |
|--------|-------------|
| Total PM terjadwal vs selesai (periode filter) | `PmCheck` + `PmCheckItem` |
| Total Tiket masuk vs ditutup | `Ticket` |
| Total Tiket Internal masuk vs ditutup | `InternalTicket` |
| Rata-rata response time tiket | `Ticket.created_at` vs `TechnicianActivity.start_time` |
| Rata-rata durasi pengerjaan tiket | `TechnicianActivity` (category=Breakdown) |
| Teknisi paling produktif (top 5) | Aggregasi semua aktivitas |
| Total jam kerja tim (Net) | `TechnicianAttendance` |
| Overall productivity % | Jam aktivitas / Jam hadir |

---

#### **Sheet 2: Detail PM per Mesin (PM Machine Detail)**
Data PM dikelompokkan per mesin dengan summary yang jelas.

| Kolom | Sumber |
|-------|--------|
| Nama Mesin (Asset) | `PmSchedule.asset.name` |
| Tipe Jadwal (weekly/daily/monthly) | `PmSchedule.schedule_type` |
| PIC/Teknisi | `PmCheck.technician.name` |
| Week Number | `PmCheck.week_number` |
| Total Item Checklist | `COUNT(PmCheckItem)` |
| Item Sudah Dicek | `PmCheckItem WHERE condition IS NOT NULL` |
| Item Belum Dicek | Selisih total - dicek |
| Item Bermasalah (NOT OK) | `PmCheckItem WHERE condition NOT IN ('ok','baik','normal')` |
| Item Butuh Follow Up | `PmCheckItem WHERE next_action IS NOT NULL` |
| Progress (%) | (Dicek / Total) × 100 |
| Tanggal Mulai Pengerjaan | `TechnicianActivity.start_time` (category=PM, reference_id=pm_check_id) |
| Tanggal Selesai | `TechnicianActivity.end_time` |
| Durasi Pengerjaan (menit) | `TechnicianActivity.duration` |
| Durasi Pause Total (menit) | `TechnicianActivity.total_pause_minutes` |
| Jumlah Pause | `TechnicianActivity.pause_count` |
| Shift | `PmCheck.shift` |
| Status PM | `PmCheck.status` |

---

#### **Sheet 3: Ringkasan PM per Teknisi (PM Technician Summary)**
Agregasi performa PM per teknisi — **berapa banyak mesin dan item yang dia kerjakan**.

| Kolom | Kalkulasi |
|-------|-----------|
| Nama Teknisi | `User.name` |
| Jumlah Mesin Ditangani | `COUNT(DISTINCT PmCheck.pm_schedule_id)` per teknisi |
| Total Item Ditugaskan | `SUM(PmCheckItem)` semua PmCheck milik teknisi |
| Total Item Selesai | Item dengan `condition IS NOT NULL` |
| Total Item Belum | Selisih |
| Progress (%) | Selesai / Total × 100 |
| Total Durasi Pengerjaan PM (jam) | `SUM(TechnicianActivity.duration)` WHERE category=PM |
| Rata-rata Item per Shift | Total item selesai / Jumlah hari kerja |
| Rata-rata Durasi per Mesin (menit) | Total durasi / Jumlah mesin |
| Item Bermasalah (NOT OK) | Count item bermasalah |
| Item Follow Up | Count item dengan next_action |

---

#### **Sheet 4: Detail Tiket Breakdown (Ticket Detail)**
Data tiket lengkap dengan **metrik waktu & teknisi**.

| Kolom | Sumber |
|-------|--------|
| No Tiket | `Ticket.ticket_no` |
| Tanggal Masuk | `Ticket.request_date` |
| Jam Masuk (Timestamp) | `Ticket.created_at` |
| Mesin/Aset | `Ticket.asset.name` |
| Subject | `Ticket.subject` |
| Requester | `Ticket.requester.name` |
| Department | `Ticket.requester.department` |
| Status | `Ticket.status` |
| Assigned To | `Ticket.assigned_to` |
| GA PIC | `Ticket.ga_pic_name` |
| MTC PIC | `Ticket.mtc_pic_name` |
| Teknisi Yang Mengerjakan | `TechnicianActivity.user.name` (semua aktivitas Breakdown untuk tiket ini) |
| Jumlah Teknisi | `COUNT(DISTINCT TechnicianActivity.user_id)` |
| Response Time | `Ticket.created_at` → pertama kali `TechnicianActivity.start_time` |
| Tanggal Mulai Dikerjakan | Pertama kali `TechnicianActivity.start_time` |
| Tanggal Selesai | `Ticket.closed_date` |
| Durasi Total Pengerjaan (menit) | `SUM(TechnicianActivity.duration)` semua sesi |
| Durasi Total Pause (menit) | `SUM(TechnicianActivity.total_pause_minutes)` |
| Jumlah Penjedaan | `SUM(TechnicianActivity.pause_count)` |
| Jumlah Sesi Pengerjaan | `COUNT(TechnicianActivity)` untuk tiket ini |
| Lead Time (jam) | `Ticket.created_at` → `Ticket.closed_date` (kalender) |
| Problem Cause | `Ticket.problem_cause` |
| Planned Date | `Ticket.planned_date` |
| PR Number | `Ticket.pr_number` |

---

#### **Sheet 5: Ringkasan Tiket per Teknisi (Ticket Technician Summary)**
Performa penanganan tiket per teknisi.

| Kolom | Kalkulasi |
|-------|-----------|
| Nama Teknisi | `User.name` |
| Total Tiket Dikerjakan | `COUNT(DISTINCT reference_id)` di TechnicianActivity (Breakdown) |
| Total Sesi Pengerjaan | `COUNT(TechnicianActivity)` per user (Breakdown) |
| Total Durasi Pengerjaan (jam) | `SUM(duration)` |
| Rata-rata Durasi per Tiket (menit) | Total durasi / jumlah tiket |
| Total Penjedaan | `SUM(pause_count)` |
| Total Durasi Pause (menit) | `SUM(total_pause_minutes)` |
| Tiket Selesai (Closed) | Tiket dengan `status=closed` yang pernah dikerjakan |
| Rata-rata Response Time (menit) | Rata-rata waktu dari tiket masuk → mulai dikerjakan |
| Rata-rata Lead Time (jam) | Rata-rata waktu dari tiket masuk → tutup |

---

#### **Sheet 6: Distribusi Tiket (Ticket Distribution)**
Statistik distribusi tiket per bulan/status/department untuk perencanaan.

| Kolom | Kalkulasi |
|-------|-----------|
| Bulan | Berdasarkan `request_date` |
| Tiket Masuk | `COUNT(Ticket)` per bulan |
| Tiket Closed | `COUNT WHERE status=closed` per bulan |
| Tiket Open | `COUNT WHERE status=open` per bulan |
| Tiket On Progress | `COUNT WHERE status=onprogress` per bulan |
| Tiket Pending/Schedule | `COUNT WHERE status=schedule` per bulan |
| Tiket Rejected | `COUNT WHERE status=rejected` per bulan |
| Rate Penyelesaian (%) | Closed / Masuk × 100 |
| Rata-rata Response Time (menit) | Per bulan |
| Rata-rata Lead Time (jam) | Per bulan |

---

#### **Sheet 7: Tiket Internal & Aktivitas Lainnya**
Menampilkan semua tiket internal + aktivitas kategori "Lain-lain".

| Kolom | Sumber |
|-------|--------|
| No Tiket Internal | `InternalTicket.ticket_no` |
| Sumber (PM/Lisan) | `InternalTicket.source_type` |
| Asal PM Item (jika ada) | `InternalTicket.pmCheckItem` |
| Tanggal Masuk | `InternalTicket.request_date` |
| Mesin/Aset | `InternalTicket.asset.name` |
| Subject | `InternalTicket.subject` |
| Deskripsi | `InternalTicket.description` |
| Ditugaskan Ke | `InternalTicket.assigned_to_name` |
| Prioritas | `InternalTicket.priority` |
| Status | `InternalTicket.status` |
| Target Date | `InternalTicket.target_date` |
| Tanggal Mulai | `InternalTicket.started_at` |
| Tanggal Selesai | `InternalTicket.closed_at` |
| Durasi Pengerjaan (menit) | `TechnicianActivity` WHERE category='Lain-lain' AND reference_id=internal_ticket_id |
| Hasil Pekerjaan | `InternalTicket.work_result` |
| Requester | `InternalTicket.requester.name` |

---

#### **Sheet 8: Scorecard Teknisi (Technician Scorecard)**
**Satu baris per teknisi** — gabungan semua metrik dari PM, Tiket, dan Internal untuk penilaian menyeluruh.

| Kolom | Sumber |
|-------|--------|
| Nama Teknisi | `User.name` |
| Role | `User.role` |
| Total Hari Hadir | `COUNT(TechnicianAttendance)` |
| Total Jam Hadir (Net) | Dari clock_in/clock_out - istirahat |
| Total Jam Aktivitas | `SUM(TechnicianActivity.duration)` semua kategori |
| Produktivitas (%) | Jam Aktivitas / Jam Hadir × 100 |
| — PM — | |
| Mesin PM Ditangani | `COUNT(DISTINCT pm_schedule_id)` |
| Item PM Selesai | Count |
| Jam Kerja PM | `SUM(duration)` WHERE category=PM |
| — Breakdown — | |
| Tiket Ditangani | `COUNT(DISTINCT reference_id)` WHERE category=Breakdown |
| Jam Kerja Breakdown | `SUM(duration)` WHERE category=Breakdown |
| Rata-rata Response Time | Kalkulasi per teknisi |
| — Lainnya — | |
| Aktivitas Lain-lain | `COUNT` WHERE category=Lain-lain |
| Jam Kerja Lainnya | `SUM(duration)` WHERE category=Lain-lain |
| — Skor — | |
| Distribusi PM (%) | Jam PM / Total Jam × 100 |
| Distribusi Breakdown (%) | Jam Breakdown / Total Jam × 100 |
| Distribusi Lainnya (%) | Jam Lainnya / Total Jam × 100 |
| Rating Performance | Excellent/Good/Fair/Poor berdasarkan produktivitas |

---

### Component 2: Controller

#### [MODIFY] [ExportController.php](file:///c:/laragon/www/K-Maint/app/Http/Controllers/ExportController.php)

Tambahkan method baru `exportManagerReport()`:
- Menerima filter: `start_date`, `end_date`, `technician_id` (opsional, default semua)
- Mengambil semua data terkait dan meneruskan ke `ManagerReportExport`
- Generate filename: `Laporan_Efektivitas_Teknisi_{start_date}_sd_{end_date}.xlsx`

---

### Component 3: Route

#### [MODIFY] [web.php](file:///c:/laragon/www/K-Maint/routes/web.php)

Tambahkan route baru di dalam group export:
```php
Route::get('/export/manager-report', [ExportController::class, 'exportManagerReport'])
    ->name('export.manager-report');
```

---

### Component 4: UI Button

#### [MODIFY] Blade monitoring view

Tambahkan tombol "📊 Export Laporan Efektivitas" di halaman monitoring team (`monitoring/team.blade.php`) dan PM monitoring (`monitoring/pm.blade.php`), dengan filter tanggal dan teknisi.

---

## Open Questions

> [!IMPORTANT]
> **Filter Tanggal Default**: Apakah default filter pakai **bulan ini** (1 Juli - 31 Juli 2026) atau **custom range**? Saya rencanakan default = bulan berjalan, tapi tetap bisa diubah.

> [!IMPORTANT]
> **Akses Export**: Apakah fitur export ini hanya untuk role `admin` dan `manager`, atau boleh diakses `spv` juga?

> [!IMPORTANT]
> **Halaman Trigger**: Mau tombol export-nya ditaruh di halaman mana? Opsi:
> 1. Halaman Monitoring Team saja (sudah ada filter periode)
> 2. Halaman baru khusus export/reporting
> 3. Di semua halaman monitoring (team + PM)

---

## Verification Plan

### Automated Tests
```bash
php artisan tinker --execute="
  \$export = new \App\Exports\ManagerReportExport(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
  echo 'Sheets count: ' . count(\$export->sheets());
"
```

### Manual Verification
1. Akses route `/export/manager-report?start_date=2026-07-01&end_date=2026-07-31`
2. Buka file Excel yang terdownload
3. Verifikasi setiap sheet memiliki data yang benar
4. Cross-check angka summary dengan data di dashboard monitoring
5. Test dengan filter teknisi spesifik
6. Test dengan rentang tanggal berbeda (harian, mingguan, bulanan)
