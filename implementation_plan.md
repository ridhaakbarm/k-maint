# Revisi Export Efektivitas Teknisi — Fokus PM Accuracy & Shift Analysis

Revisi export Excel untuk memperbaiki akurasi data PM dan menambahkan analisis per shift. Berdasarkan temuan: teknisi bisa record 57 menit aktivitas PM tapi 0 item dicek, namun tetap dihitung "1 mesin dikerjakan" — ini misleading.

## Root Cause Analysis

### Bug: PM Machine Count yang Salah

Pada implementasi saat ini di [ManagerReportExport.php](file:///c:/laragon/www/K-Maint/app/Exports/ManagerReportExport.php):

```php
// Line 240 — Sheet 3: Summary PM Teknisi
$machineCount = $checks->pluck('pm_schedule_id')->filter()->unique()->count();
```

**Masalah:** Ini menghitung "jumlah mesin ditangani" berdasarkan `PmCheck` yang di-assign ke teknisi (`technician_id`). Tapi `technician_id` di `PmCheck` hanya menandai siapa yang **membuka** checklist PM, bukan siapa yang **benar-benar mengecek item**.

**Skenario bermasalah:**
1. Teknisi A buka PM → `PmCheck.technician_id = A`
2. Teknisi A record `TechnicianActivity` (PM) → 57 menit
3. Tapi Teknisi A **tidak mengisi condition** satu pun `PmCheckItem` → progress 0%
4. Export tetap menghitung: **"Teknisi A: 1 mesin ditangani"** ❌

**Sumber kebenaran yang seharusnya:** `PmCheckItem.checked_by_user_id` — ini mencatat siapa yang **benar-benar** mengecek per item (diset di [PmCheckController.php L358-359](file:///c:/laragon/www/K-Maint/app/Http/Controllers/PmCheckController.php#L358-L359)).

---

## Proposed Changes

### Component 1: Export Class (Revisi)

#### [MODIFY] [ManagerReportExport.php](file:///c:/laragon/www/K-Maint/app/Exports/ManagerReportExport.php)

---

#### **Perubahan 1: Fix Data Source PM — Gunakan `PmCheckItem.checked_by_user_id`**

Ubah **seluruh logika PM summary** dari berbasis `PmCheck.technician_id` menjadi berbasis `PmCheckItem.checked_by_user_id`:

| Sebelum (salah) | Sesudah (benar) |
|---|---|
| Mesin dikerjakan = `PmCheck WHERE technician_id = X` | Mesin dikerjakan = `PmCheckItem WHERE checked_by_user_id = X AND condition IS NOT NULL` → group by mesin via `pmCheck.pmSchedule.asset` |
| Item selesai = semua `PmCheckItem` dari PmCheck milik teknisi | Item selesai = hanya `PmCheckItem WHERE checked_by_user_id = X` |
| Durasi PM = `TechnicianActivity(PM)` per user | Durasi PM = tetap `TechnicianActivity(PM)` per user (ini sudah benar) |

**Tambahan load data di `loadData()`:**
```php
// Load semua PmCheckItem dengan checked_by_user_id untuk periode ini
$this->pmCheckItems = PmCheckItem::with([
    'pmCheck.pmSchedule.asset',
    'checklistTemplate',
    'checkedBy',
])
->whereHas('pmCheck', function ($q) {
    $q->whereBetween('check_date', [$this->startDate, $this->endDate])
      ->orWhere(function ($fallback) {
          $fallback->whereNull('check_date')
              ->whereBetween('due_date', [$this->startDate, $this->endDate]);
      });
})
->get();
```

---

#### **Perubahan 2: Hapus Kolom PIC PM di Semua Sheet**

| Sheet | Kolom Dihapus |
|---|---|
| Sheet 2 (Detail PM Mesin) | Hapus kolom `PIC/Teknisi` (dari `PmCheck.technician.name`) |
| Sheet 8 (Scorecard) | Tidak ada kolom PIC, sudah oke |

**Ganti** kolom `PIC/Teknisi` dengan `Dikerjakan Oleh` → daftar nama unik dari `PmCheckItem.checked_by_user_id` per PmCheck.

---

#### **Perubahan 3: Tambah Kolom Efektivitas PM di Sheet 2 & 3**

**Sheet 2 (Detail PM Mesin)** — Tambah kolom baru:

| Kolom Baru | Kalkulasi | Tujuan |
|---|---|---|
| Efektivitas | `(Item Dicek / Durasi Menit)` × 60 = "item/jam" | Berapa item per jam — semakin tinggi semakin efektif |
| Flag Efektivitas | `Efektif` jika progress ≥ 50% DAN durasi > 0, `Tidak Efektif` jika durasi > 0 tapi progress = 0%, `Belum Dikerjakan` jika durasi = 0 | Quick glance untuk manager |
| Dikerjakan Oleh | Nama-nama unik dari `PmCheckItem.checked_by_user_id` | Siapa yang benar-benar ngecek |

**Sheet 3 (Summary PM Teknisi)** — Revisi + tambah kolom:

| Kolom | Perubahan |
|---|---|
| ~~Jumlah Mesin Ditangani~~ → `Mesin Benar-Benar Dicek` | Hitung hanya mesin di mana teknisi punya `PmCheckItem.checked_by_user_id = X AND condition IS NOT NULL` |
| ~~Total Item Ditugaskan~~ (HAPUS) | Tidak relevan karena semua orang bisa kerjakan semua PM |
| Total Item Dicek | `COUNT(PmCheckItem WHERE checked_by_user_id = X AND condition IS NOT NULL)` |
| Item OK | `WHERE condition IN ('ok','baik','normal')` |
| Item Bermasalah | `WHERE condition NOT IN ('ok','baik','normal')` |
| Durasi Total PM (jam) | Dari `TechnicianActivity(PM)` |
| Efektivitas (item/jam) | `Item Dicek / (Durasi / 60)` |
| Mesin Tanpa Progress | Mesin di mana teknisi record aktivitas PM tapi 0 item dicek |
| Rata-rata Item per Hari Kerja | `Item dicek / jumlah hari hadir` |

---

#### **Perubahan 4: Sheet Baru — Distribusi Per Shift**

Tambah **Sheet 9: "Distribusi per Shift"** — menampilkan breakdown waktu per shift.

**Data source:**
- `TechnicianAttendance.shift` → menandai shift saat teknisi clock-in
- `TechnicianActivity.start_time` → match dengan tanggal attendance untuk menentukan shift hari itu
- Join: `TechnicianActivity.user_id + DATE(start_time)` ↔ `TechnicianAttendance.user_id + date`

**Tabel utama (per baris = 1 shift group):**

| Kolom | Sumber |
|---|---|
| Shift | `TechnicianAttendance.shift` (1/2/3) |
| Total Hari | Jumlah attendance record per shift |
| Total Teknisi (unik) | `COUNT(DISTINCT user_id)` per shift |
| Total Jam Aktivitas | `SUM(TechnicianActivity.duration)` per shift |
| Jam PM | `SUM(duration) WHERE category = PM` per shift |
| Jam Breakdown | `SUM(duration) WHERE category = Breakdown` per shift |
| Jam Lain-lain | `SUM(duration) WHERE category = Lain-lain` per shift |
| % PM | `Jam PM / Total Jam × 100` |
| % Breakdown | `Jam Breakdown / Total Jam × 100` |
| % Lain-lain | `Jam Lain-lain / Total Jam × 100` |
| Rata-rata Jam per Hari | `Total Jam / Total Hari` |
| Item PM Dicek per Shift | Jumlah `PmCheckItem WHERE checked_at` jatuh di shift |

**Sub-tabel (per baris = 1 teknisi per shift):**

| Kolom | Sumber |
|---|---|
| Nama Teknisi | `User.name` |
| Shift | Dari attendance |
| Hari Masuk Shift Ini | `COUNT(TechnicianAttendance WHERE shift = X)` |
| Jam PM | SUM durasi PM |
| Jam Breakdown | SUM durasi Breakdown |
| Jam Lain-lain | SUM durasi Lain-lain |
| Total Jam | Sum semua |
| Item PM Dicek | Count items checked |
| Produktivitas (%) | Jam Aktivitas / Jam Hadir × 100 |

---

#### **Perubahan 5: Update Sheet 1 (Executive Summary)**

Tambah baris baru di summary:

| Metrik Baru | Kalkulasi |
|---|---|
| Total Mesin PM Benar-Benar Dicek | Mesin dengan ≥1 item checked |
| Total Mesin PM Tanpa Progress | Mesin dengan activity record tapi 0 item checked |
| Shift Paling Produktif | Shift dengan rata-rata jam aktivitas/hari tertinggi |
| Shift dengan PM Terbanyak | Shift dengan total item PM dicek terbanyak |

---

#### **Perubahan 6: Update Sheet 8 (Scorecard Teknisi)**

| Kolom | Perubahan |
|---|---|
| ~~Mesin PM Ditangani~~ → `Mesin PM Benar Dicek` | Hanya hitung mesin dengan ≥1 item checked |
| ~~Item PM Selesai~~ → `Item PM Dicek` | Hanya dari `checked_by_user_id` |
| (BARU) `Efektivitas PM (item/jam)` | Item dicek / jam PM |
| (BARU) `Shift Utama` | Shift yang paling sering dipakai teknisi ini |

---

### Ringkasan Semua Perubahan per Sheet

| Sheet | Aksi |
|---|---|
| 1. Ringkasan Eksekutif | **UPDATE** — tambah metrik PM akurat + shift paling produktif |
| 2. Detail PM Mesin | **UPDATE** — hapus PIC, tambah Dikerjakan Oleh, Flag Efektivitas |
| 3. Summary PM Teknisi | **OVERHAUL** — ganti basis dari `technician_id` ke `checked_by_user_id` |
| 4. Detail Tiket Breakdown | Tidak berubah |
| 5. Summary Tiket Teknisi | Tidak berubah |
| 6. Distribusi Tiket | Tidak berubah |
| 7. Internal dan Lainnya | Tidak berubah |
| 8. Scorecard Teknisi | **UPDATE** — fix PM metrics + tambah shift utama |
| 9. Distribusi per Shift | **NEW** — analisis waktu per shift |

---

## Verification Plan

### Manual Verification
1. **Test case kritis**: Temukan teknisi dengan `TechnicianActivity(PM)` tapi 0 `PmCheckItem.condition` → verifikasi export menampilkan:
   - `Mesin Benar Dicek = 0` (bukan 1)
   - `Flag = Tidak Efektif`
   - `Item Dicek = 0`
   - `Efektivitas = 0 item/jam`
2. **Test PIC dihapus**: Pastikan tidak ada kolom PIC PM di sheet manapun
3. **Test shift analysis**: Verifikasi Sheet 9 menampilkan breakdown per Shift 1/2/3 yang benar
4. **Cross-check**: Bandingkan angka di Sheet 9 (shift) dengan Sheet 8 (scorecard) — total harus cocok

### Automated Tests
```bash
php artisan tinker --execute="
  // Cek bahwa ada PmCheckItem dengan checked_by_user_id
  echo 'Items with checker: ' . \App\Models\PmCheckItem::whereNotNull('checked_by_user_id')->count();
  echo PHP_EOL;
  // Cek shift data
  echo 'Attendance with shift: ' . \App\Models\TechnicianAttendance::whereNotNull('shift')->count();
"
```
