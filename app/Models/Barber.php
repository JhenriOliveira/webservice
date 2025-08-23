<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'barbershop_id',
        'name',
        'email',
        'phone',
        'photo_base64',
        'photo_type',
        'specialties',
        'description',
        'experience_years',
        'rating',
        'active',
        'start_time',
        'end_time',
        'working_days'
    ];

    protected $casts = [
        'active' => 'boolean',
        'rating' => 'decimal:2',
        'experience_years' => 'integer',
        'working_days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com barbearia
     */
    public function barbershop()
    {
        return $this->belongsTo(Barbershop::class);
    }

    /**
     * Relacionamento com agendamentos
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Acessor para a foto
     */
    public function getPhotoAttribute()
    {
        if ($this->photo_base64 && $this->photo_type) {
            return 'data:image/' . $this->photo_type . ';base64,' . $this->photo_base64;
        }
        return null;
    }

    /**
     * Scope para barbeiros ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Scope para barbeiros de uma barbearia específica
     */
    public function scopeFromBarbershop($query, $barbershopId)
    {
        return $query->where('barbershop_id', $barbershopId);
    }
}