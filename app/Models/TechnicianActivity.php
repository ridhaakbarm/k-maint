<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TechnicianActivity extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'description',
        'reference_id',
        'start_time',
        'end_time',
        'duration',
        'status',
        'paused_at',
        'resumed_at',
        'total_pause_minutes',
        'pause_count',
        'pause_reason',
        'pause_resume_log',
        'resumed_from_activity_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'pause_resume_log' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    // Mengambil data tiket jika kategori adalah Breakdown
    public function ticket() {
        return $this->belongsTo(Ticket::class, 'reference_id');
    }

    // Mengambil data PM jika kategori adalah PM
    public function pmCheck() {
        return $this->belongsTo(PmCheck::class, 'reference_id');
    }

    public function internalTicket()
    {
        return $this->belongsTo(InternalTicket::class, 'reference_id');
    }

    public function resumedFromActivity()
    {
        return $this->belongsTo(self::class, 'resumed_from_activity_id');
    }

    public function calculateEffectiveDuration($endTime = null): int
    {
        if (!$this->start_time) {
            return 0;
        }

        $effectiveEnd = $endTime;

        if (!$effectiveEnd) {
            if ($this->status === 'paused' && $this->paused_at) {
                $effectiveEnd = $this->paused_at;
            } else {
                $effectiveEnd = $this->end_time ?: now();
            }
        }

        if (!$effectiveEnd instanceof Carbon) {
            $effectiveEnd = Carbon::parse($effectiveEnd);
        }

        $totalMinutes = $this->start_time->diffInMinutes($effectiveEnd);

        return max(0, $totalMinutes - ($this->total_pause_minutes ?? 0));
    }

    public function pause(?string $reason = null, $pausedAt = null): void
    {
        $pausedAt = $pausedAt instanceof Carbon ? $pausedAt : Carbon::parse($pausedAt ?: now());
        $log = $this->pause_resume_log ?? [];
        $log[] = [
            'type' => 'paused',
            'at' => $pausedAt->toDateTimeString(),
            'reason' => $reason,
        ];

        $this->update([
            'status' => 'paused',
            'paused_at' => $pausedAt,
            'pause_reason' => $reason,
            'pause_count' => ($this->pause_count ?? 0) + 1,
            'duration' => $this->calculateEffectiveDuration($pausedAt),
            'pause_resume_log' => $log,
        ]);
    }

    public function resume($resumedAt = null, array $context = []): void
    {
        $resumedAt = $resumedAt instanceof Carbon ? $resumedAt : Carbon::parse($resumedAt ?: now());
        $pausedAt = $this->paused_at;
        $additionalPause = $pausedAt ? $pausedAt->diffInMinutes($resumedAt) : 0;

        $log = $this->pause_resume_log ?? [];
        $log[] = array_filter([
            'type' => 'resumed',
            'at' => $resumedAt->toDateTimeString(),
            'by_user_id' => $context['by_user_id'] ?? null,
            'by_user_name' => $context['by_user_name'] ?? null,
            'note' => $context['note'] ?? null,
        ], fn($value) => !is_null($value));

        $this->update([
            'status' => 'running',
            'paused_at' => null,
            'resumed_at' => $resumedAt,
            'pause_reason' => null,
            'total_pause_minutes' => ($this->total_pause_minutes ?? 0) + $additionalPause,
            'duration' => $this->calculateEffectiveDuration($pausedAt ?: $resumedAt),
            'pause_resume_log' => $log,
        ]);
    }

    public function complete($endTime = null): void
    {
        $endTime = $endTime instanceof Carbon ? $endTime : Carbon::parse($endTime ?: now());

        $this->update([
            'status' => 'completed',
            'end_time' => $endTime,
            'duration' => $this->calculateEffectiveDuration($endTime),
        ]);
    }

    // Accessor: Hitung durasi otomatis jika duration kosong atau 0
    public function getDurationAttribute($value)
    {
        if (in_array($this->status, ['running', 'paused', 'completed'], true) && $this->start_time) {
            if ($this->status === 'paused' && $this->paused_at) {
                return $this->calculateEffectiveDuration($this->paused_at);
            }

            if ($this->status === 'completed' && $this->end_time) {
                return $this->calculateEffectiveDuration($this->end_time);
            }

            if ($this->status === 'running') {
                return $this->calculateEffectiveDuration(now());
            }
        }

        if ($value && $value > 0) {
            return $value;
        }

        return $value ?? 0;
    }
}
