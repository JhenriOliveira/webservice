<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Barbershop;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function createAppointment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $barber = Barber::findOrFail($data['barber_id']);
            $services = Service::whereIn('id', $data['service_ids'])->get();
            
            // Calcular duração total e preço
            $totalDuration = $services->sum('duration_minutes');
            $totalPrice = $services->sum('price');
            
            // Verificar disponibilidade
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($totalDuration);
            
            if (!$this->isTimeSlotAvailable($barber->id, $startTime, $endTime)) {
                throw new \Exception('Horário indisponível');
            }
            
            // Criar agendamento
            $appointment = Appointment::create([
                'barbershop_id' => $barber->barbershop_id,
                'barber_id' => $barber->id,
                'user_id' => $data['user_id'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_duration_minutes' => $totalDuration,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Vincular serviços
            foreach ($services as $service) {
                $appointment->services()->attach($service->id, [
                    'price' => $service->price,
                    'duration_minutes' => $service->duration_minutes
                ]);
            }
            
            return $appointment;
        });
    }
    
    public function isTimeSlotAvailable($barberId, $startTime, $endTime)
    {
        $conflictingAppointments = Appointment::where('barber_id', $barberId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();
            
        return !$conflictingAppointments;
    }
    
    public function getAvailableSlots($barberId, $date, $serviceIds = [])
    {
        $barber = Barber::findOrFail($barberId);
        $barbershop = $barber->barbershop;
        
        $totalDuration = Service::whereIn('id', $serviceIds)->sum('duration_minutes');
        $date = Carbon::parse($date);
        
        $slots = [];
        $currentTime = $date->copy()->setTimeFrom($barbershop->opening_time);
        $closingTime = $date->copy()->setTimeFrom($barbershop->closing_time);
        
        while ($currentTime->copy()->addMinutes($totalDuration) <= $closingTime) {
            $slotEnd = $currentTime->copy()->addMinutes($totalDuration);
            
            if ($this->isTimeSlotAvailable($barberId, $currentTime, $slotEnd)) {
                $slots[] = $currentTime->format('H:i');
            }
            
            // Avançar em intervalos de 15 minutos
            $currentTime->addMinutes(15);
        }
        
        return $slots;
    }
}