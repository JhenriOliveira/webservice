<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Barbershop extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'corporate_name',
        'tax_id',
        'phone',
        'email',
        'description',
        'address',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'opening_time',
        'closing_time',
        'average_service_time',
        'accepts_online_scheduling',
        'active',
        'profile_photo_base64',
        'profile_photo_type',
        'social_media',
        'working_days'
    ];

    protected $casts = [
        'social_media' => 'array',
        'working_days' => 'array',
        'active' => 'boolean',
        'accepts_online_scheduling' => 'boolean',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    // Relacionamento com o usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Acessor para a foto de perfil
    public function getProfilePhotoAttribute()
    {
        if ($this->profile_photo_base64 && $this->profile_photo_type) {
            return 'data:image/' . $this->profile_photo_type . ';base64,' . $this->profile_photo_base64;
        }
        
        return null;
    }

    // Mutator para processar upload de imagem
    public function setProfilePhotoBase64Attribute($value)
    {
        if ($value) {
            // Remove o prefixo data:image/xxx;base64, se existir
            if (preg_match('/^data:image\/(\w+);base64,/', $value, $type)) {
                $value = substr($value, strpos($value, ',') + 1);
                $this->attributes['profile_photo_type'] = $type[1];
            }
            
            $this->attributes['profile_photo_base64'] = $value;
        } else {
            $this->attributes['profile_photo_base64'] = null;
            $this->attributes['profile_photo_type'] = null;
        }
    }

    // Relacionamentos
    public function barbers()
    {
        return $this->hasMany(Barber::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}