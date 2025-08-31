<?php
// app/Models/Client.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cpf',
        'phone',
        'phone_secondary',
        'birth_date',
        'gender',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'preferences',
        'allergies',
        'medical_conditions',
        'loyalty_points'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'preferences' => 'array',
        'allergies' => 'array',
        'medical_conditions' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'loyalty_points' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'client_id', 'user_id');
    }

    public function getFullNameAttribute()
    {
        return $this->user->name;
    }

    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    public function getFullAddressAttribute()
    {
        $address = [];
        if ($this->street) $address[] = $this->street;
        if ($this->number) $address[] = $this->number;
        if ($this->complement) $address[] = $this->complement;
        if ($this->neighborhood) $address[] = $this->neighborhood;
        if ($this->city) $address[] = $this->city;
        if ($this->state) $address[] = $this->state;
        if ($this->zip_code) $address[] = 'CEP: ' . $this->zip_code;
        
        return implode(', ', $address);
    }

    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude
            ];
        }
        
        return null;
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })->orWhere('cpf', 'like', "%{$search}%")
          ->orWhere('phone', 'like', "%{$search}%")
          ->orWhere('street', 'like', "%{$search}%")
          ->orWhere('city', 'like', "%{$search}%");
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw("
                        *, 
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude)))) AS distance
                    ", [$latitude, $longitude, $latitude])
                    ->having('distance', '<', $radius)
                    ->orderBy('distance');
    }
}