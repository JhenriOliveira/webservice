<?php
// app/Models/Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'barber_id',
        'client_id',
        'barbershop_id',
        'start_time',
        'end_time',
        'total_price',
        'total_duration',
        'notes',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    // Relacionamentos
    public function barber()
    {
        return $this->belongsTo(Barber::class, 'barber_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function barbershop()
    {
        return $this->belongsTo(Barbershop::class, 'barbershop_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'appointments_services', 'appointment_id', 'service_id')
            ->withPivot('price', 'duration')
            ->withTimestamps();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'appointments_products', 'appointment_id', 'product_id')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    // Métodos de negócio
    public function calculateTotals()
    {
        $totalPrice = 0;
        $totalDuration = 0;

        // Soma serviços
        foreach ($this->services as $service) {
            $totalPrice += $service->pivot->price;
            $totalDuration += $service->pivot->duration;
        }

        // Soma produtos
        foreach ($this->products as $product) {
            $totalPrice += $product->pivot->price * $product->pivot->quantity;
        }

        $this->total_price = $totalPrice;
        $this->total_duration = $totalDuration;
        $this->end_time = Carbon::parse($this->start_time)->addMinutes($totalDuration);

        return $this;
    }

    public function isWithinBusinessHours()
    {
        $barbershop = $this->barbershop;
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        if (empty($barbershop->opening_time) || empty($barbershop->closing_time)) {
            return false;
        }
    
        $openingTime = Carbon::parse($barbershop->opening_time);
        $closingTime = Carbon::parse($barbershop->closing_time);
        
        $openingTime->setDate($startTime->year, $startTime->month, $startTime->day);
        $closingTime->setDate($startTime->year, $startTime->month, $startTime->day);
        
        return $startTime->between($openingTime, $closingTime) && 
               $endTime->between($openingTime, $closingTime);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
                    ->orderBy('start_time');
    }

    public function scopeHistory($query)
    {
        return $query->where('start_time', '<', now())
                    ->orderBy('start_time', 'desc');
    }

    public function scopeForBarber($query, $barberId)
    {
        return $query->where('barber_id', $barberId);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}