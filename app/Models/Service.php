<?php
// app/Models/Service.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
        'barbershop_id',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function barbershop()
    {
        return $this->belongsTo(Barbershop::class, 'barbershop_id');
    }

    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointments_services', 'service_id', 'appointment_id')
            ->withPivot('price', 'duration_minutes')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBarbershop($query, $barbershopId)
    {
        return $query->where('barbershop_id', $barbershopId);
    }
}