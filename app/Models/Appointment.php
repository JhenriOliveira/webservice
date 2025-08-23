<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'barbershop_id',
        'barber_id',
        'user_id',
        'start_time',
        'end_time',
        'total_duration_minutes',
        'total_price',
        'status',
        'notes',
        'barber_notes'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['price', 'duration_minutes'])
            ->withTimestamps();
    }

    // Método para calcular horários disponíveis
    public static function getAvailableSlots($barberId, $date, $serviceIds = [])
    {
        // Implementar lógica de disponibilidade
    }
}