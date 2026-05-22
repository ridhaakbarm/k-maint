<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

   protected $fillable = [
    'ticket_no',
    'request_date',
    'requester_id',
    'subject',
    'description',
    'attachment',
    'after_photo',
    'assigned_to',
    'status',
    'ga_notes',
    'user_notes',
    'rejected_date',
    'closed_date',
    'category_id', // Ini nanti bisa kamu hapus kalau sudah tidak pakai kategori
    'area_id',     // Ini juga bisa dihapus kalau sudah tidak pakai area
    'asset_id',
    'machine_part_id', // <--- TAMBAHKAN INI
    'assigned_types',
    'internal_types',
    'ga_pic_name',
    'mtc_pic_name',
    'mtc_ticket_link',
    'vendor_details',
    'assigned_at',
    'assigned_by',
    'planned_date',
    'estimated_date',
    'pr_number',
    'coordination_notes',
    'external_vendor',
    'external_notes',
    'problem_cause',
    'serah_terima_teknisi',
    'serah_terima_user',
    'was_rejected'
];

   protected $casts = [
         'request_date' => 'date',
        'rejected_date' => 'datetime',
        'closed_date' => 'datetime',
        'assigned_at' => 'datetime',
        'assigned_types' => 'array',
        'internal_types' => 'array',
        'vendor_details' => 'array',
        'planned_date' => 'date',
        'estimated_date' => 'date',
        
    ];

    // Event untuk membuat notifikasi otomatis
    
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public static function generateTicketNo()
{
    // 1. Format TahunBulan (YYMM), misal: 2601
    $datePart = now()->format('ym'); 
    
    // 2. Gabungkan dengan string 'TICKET-' di depannya
    // Hasilnya misal: TICKET-2601-
    $fullPrefix = 'TICKET-' . $datePart . '-';

    // 3. Cari tiket terakhir yang nomornya dimulai dengan prefix tersebut
    $latestTicket = self::where('ticket_no', 'LIKE', $fullPrefix . '%')
                        ->latest('id')
                        ->first();

    if (!$latestTicket) {
        // Jika belum ada tiket di bulan ini, mulai dari 1
        $sequence = 1;
    } else {
        // Jika sudah ada, ambil 5 angka terakhir dari ticket_no
        // Contoh: dari "TICKET-2601-00005" diambil "00005", lalu + 1 = 6
        $lastSequence = (int) substr($latestTicket->ticket_no, -5);
        $sequence = $lastSequence + 1;
    }

    // 4. Gabungkan semuanya dengan padding 5 digit (00001)
    return $fullPrefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
}

    // Method untuk statistik
public static function getStatistics($userId = null)
{
    $query = self::query();
    
    if ($userId) {
        $query->where('requester_id', $userId);
    }

    $total = $query->count();
    $open = $query->clone()->where('status', 'open')->count();
    $onprogress = $query->clone()->where('status', 'onprogress')->count();
    $request_to_close = $query->clone()->where('status', 'request_to_close')->count();
    $closed = $query->clone()->where('status', 'closed')->count();
    $rejected= $query->clone()->where('status', 'rejected')->count();
    $schedule = $query->clone()->where('status', 'schedule')->count();

    return [
        'total' => $total,
        'open' => $open,
        'onprogress' => $onprogress,
        'request_to_close' => $request_to_close,
        'closed' => $closed,
        'rejected' => $rejected,
        'schedule' => $schedule,
        
    ];
}
    // Method untuk notifikasi ticket baru
    public static function getNewTicketsCount()
    {
        return self::where('status', 'open')->count();
    }

    // Category & Area relations disabled - models not in use
    // public function category()
    // {
    //     return $this->belongsTo(Category::class);
    // }

    // public function area()
    // {
    //     return $this->belongsTo(Area::class);
    // }

      public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class)->latest();
    }

     protected static function booted()
    {
        static::created(function ($ticket) {
            // Catat status awal ketika ticket dibuat
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->requester_id,
                'old_status' => null,
                'new_status' => 'open',
                'notes' => 'Ticket dibuat'
            ]);

            // Buat notifikasi untuk admin dan GA ketika ticket baru dibuat
            if (class_exists('App\Models\Notification')) {
                \App\Models\Notification::createNewTicketNotification($ticket);
            }
        });

        static::updated(function ($ticket) {
            // Catat perubahan status
            if ($ticket->isDirty('status')) {
                $oldStatus = $ticket->getOriginal('status');
                $newStatus = $ticket->status;
                
                // Dapatkan user yang melakukan perubahan (dari request atau auth)
                $userId = auth()->id() ?? $ticket->requester_id;
                
                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'notes' => $ticket->ga_notes // atau notes lainnya
                ]);

                // Buat notifikasi ketika status berubah
                if (class_exists('App\Models\Notification')) {
                    \App\Models\Notification::createStatusUpdateNotification($ticket, $oldStatus, $newStatus);
                }
            }
        });
    }

  

public function asset()
{
    return $this->belongsTo(\App\Models\Asset::class);
}


   public function compileAssignedTo()
    {
        $assignedData = [];

        // Internal assignments
        if ($this->assigned_types && in_array('internal', $this->assigned_types)) {
        if ($this->internal_types && in_array('ga', $this->internal_types) && $this->ga_pic_name) {
            $assignedData[] = "MTC: {$this->ga_pic_name}";
        }
        // UBAH BAGIAN INI: Dari mtc_ticket_link menjadi mtc_pic_name kawan
        if ($this->internal_types && in_array('mtc', $this->internal_types) && $this->mtc_pic_name) {
            $assignedData[] = "MTC: {$this->mtc_pic_name}";
        }
        }

        // External assignments
        if ($this->assigned_types && in_array('external', $this->assigned_types)) {
            $vendorData = [];
            if ($this->vendor_name) $vendorData[] = $this->vendor_name;
            if ($this->vendor_contact_person) $vendorData[] = "PIC: {$this->vendor_contact_person}";
            if ($this->vendor_phone) $vendorData[] = "HP: {$this->vendor_phone}";

            if (!empty($vendorData)) {
                $assignedData[] = "External: " . implode(', ', $vendorData);
            }
        }

        return implode(' | ', $assignedData);
    }


     public function markAsAssigned($assignedBy = null)
    {
        $this->update([
            'assigned_at' => now(),
            'assigned_by' => $assignedBy ?? auth()->user()->name,
            'assigned_to' => $this->compileAssignedTo()
        ]);
    }
/**
 * Relasi ke Bagian Mesin
 */
public function machinePart()
{
    return $this->belongsTo(MachinePart::class, 'machine_part_id');
}

/**
 * Relasi untuk sistem obrolan / catatan pengerjaan
 */
public function notes()
{
    return $this->hasMany(TicketNote::class)->latest();
}

/**
 * Relasi ke TechnicianActivity (Monitoring Board)
 */
public function technicianActivities()
{
    return $this->hasMany(TechnicianActivity::class, 'reference_id')->where('category', 'Breakdown');
}

/**
 * Cek apakah tiket sedang dijeda (onprogress tapi tidak ada aktivitas running)
 */
public function isPaused()
{
    if ($this->status !== 'onprogress') {
        return false;
    }

    // Cek apakah ada aktivitas yang sedang running untuk tiket ini
    $hasRunningActivity = $this->technicianActivities()
        ->where('status', 'running')
        ->exists();

    // Tiket dijeda jika status onprogress tapi tidak ada aktivitas running
    return !$hasRunningActivity && $this->technicianActivities()->exists();
}
}