<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany; // Untuk type-hinting relasi

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'whatsapp', // tambahkan ini
        'password',
        'department',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relasi ---

    /**
     * Get the tickets requested by the user.
     */
    public function tickets(): HasMany
    {
        // Tetap menggunakan 'requester_id' seperti yang sudah Anda definisikan
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the ticket notes for the user.
     */
    public function ticketNotes(): HasMany
    {
        return $this->hasMany(\App\Models\TicketNote::class);
    }

    /**
     * Get the PM checks where user is the technician.
     */
    public function pmChecksAsTechnician(): HasMany
    {
        return $this->hasMany(\App\Models\PmCheck::class, 'technician_id');
    }

    /**
     * Get the PM checks where user is the admin.
     */
    public function pmChecksAsAdmin(): HasMany
    {
        return $this->hasMany(\App\Models\PmCheck::class, 'admin_id');
    }

    /**
     * Get the technician attendances (clock in/out) for the user.
     */
    public function technicianAttendances(): HasMany
    {
        return $this->hasMany(\App\Models\TechnicianAttendance::class);
    }

    // --- Roles Checking ---

    private function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function isAdmin(): bool
{
    return $this->normalizedRole() === 'admin';
}

public function isGA(): bool
{
    return $this->normalizedRole() === 'ga';
}

public function isMTC(): bool
{
    return in_array($this->normalizedRole(), ['mtc', 'teknisi', 'technician'], true);
}

// TAMBAHKAN DUA FUNGSI INI:
public function isManager(): bool
{
    return $this->normalizedRole() === 'manager' || $this->isAdmin(); // Admin juga dianggap manager
}

public function isSPV(): bool
{
    return $this->normalizedRole() === 'spv' || $this->isAdmin(); // Admin juga dianggap SPV
}

public function isTechnician(): bool
{
    return $this->isMTC() || $this->isAdmin(); // Admin juga boleh bertindak sebagai teknisi
}

public function isUser(): bool
{
    return $this->normalizedRole() === 'user';
}

    // --- Notifikasi Kustom ---

    /**
     * Get the unread notifications query builder.
     */
    public function unreadNotifications()
    {
        // Memanggil scope unread() dari model Notification
        return $this->notifications()->unread();
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadNotificationsCount(): int
    {
        // Menghitung langsung dari query builder
        return $this->unreadNotifications()->count();
    }

    public function hasRequestToCloseTicket()
{
    return $this->tickets()
        ->where('status', 'request_to_close')
        ->exists();
}

public function getRequestToCloseTicket()
{
    return $this->tickets()
        ->where('status', 'request_to_close')
        ->first();
}
}
