<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function createAppointment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $totalDuration = 0;
            $totalPrice = 0;

            if (!empty($data['service_ids'])) {
                foreach ($data['service_ids'] as $serviceId) {
                    $service = Service::active()->findOrFail($serviceId);
                    $totalDuration += $service->duration_minutes;
                    $totalPrice += $service->price;
                }
            }

            if (!empty($data['products'])) {
                foreach ($data['products'] as $productData) {
                    $product = Product::active()->inStock()->findOrFail($productData['id']);
                    $totalPrice += $product->price * $productData['quantity'];
                }
            }

            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($totalDuration);

            if (!$this->isBarberAvailable($data['barber_id'], $startTime, $endTime)) {
                throw ValidationException::withMessages([
                    'barber_id' => 'O barbeiro não está disponível neste horário.'
                ]);
            }

            if (!$this->isWithinBusinessHours($data['barber_id'], $startTime, $endTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'O horário selecionado está fora do funcionamento.'
                ]);
            }

            $appointment = Appointment::create([
                'barber_id' => $data['barber_id'],
                'client_id' => $data['client_id'],
                'barbershop_id' => $data['barbershop_id'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_price' => $totalPrice,
                'total_duration' => $totalDuration,
                'notes' => $data['notes'] ?? null,
                'status' => 'scheduled'
            ]);

            if (!empty($data['service_ids'])) {
                foreach ($data['service_ids'] as $serviceId) {
                    $service = Service::active()->findOrFail($serviceId);

                    $appointment->services()->attach($serviceId, [
                        'price' => $service->price,
                        'duration' => $service->duration_minutes
                    ]);
                }
            }

            if (!empty($data['products'])) {
                foreach ($data['products'] as $productData) {
                    $product = Product::active()->inStock()->findOrFail($productData['id']);

                    if ($product->stock_quantity < $productData['quantity']) {
                        throw ValidationException::withMessages([
                            'products' => "Produto {$product->name} não tem estoque suficiente."
                        ]);
                    }

                    $appointment->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price
                    ]);

                    $product->decreaseStock($productData['quantity']);
                }
            }

            return $appointment->load(['barber', 'client', 'barbershop', 'services', 'products']);
        });
    }

    public function updateAppointment(Appointment $appointment, array $data)
    {
        return DB::transaction(function () use ($appointment, $data) {
            $originalProducts = $appointment->products->keyBy('id');

            foreach ($originalProducts as $product) {
                $product->increaseStock($product->pivot->quantity);
            }

            $totalDuration = 0;
            $totalPrice = 0;

            if (isset($data['service_ids'])) {
                foreach ($data['service_ids'] as $serviceId) {
                    $service = Service::active()->findOrFail($serviceId);
                    $totalDuration += $service->duration_minutes;
                    $totalPrice += $service->price;
                }
            } else {
                foreach ($appointment->services as $service) {
                    $totalDuration += $service->pivot->duration;
                    $totalPrice += $service->pivot->price;
                }
            }

            if (isset($data['products'])) {
                foreach ($data['products'] as $productData) {
                    $product = Product::active()->inStock()->findOrFail($productData['id']);
                    $totalPrice += $product->price * $productData['quantity'];
                }
            } else {
                foreach ($appointment->products as $product) {
                    $totalPrice += $product->pivot->price * $product->pivot->quantity;
                }
            }

            $startTime = isset($data['start_time']) 
                ? Carbon::parse($data['start_time']) 
                : Carbon::parse($appointment->start_time);
                
            $endTime = $startTime->copy()->addMinutes($totalDuration);

            if (!$this->isBarberAvailable($appointment->barber_id, $startTime, $endTime, $appointment->id)) {
                throw ValidationException::withMessages([
                    'barber_id' => 'O barbeiro não está disponível neste horário.'
                ]);
            }

            if (!$this->isWithinBusinessHours($appointment->barber_id, $startTime, $endTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'O horário selecionado está fora do funcionamento.'
                ]);
            }

            $appointment->update([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_price' => $totalPrice,
                'total_duration' => $totalDuration,
                'notes' => $data['notes'] ?? $appointment->notes,
                'status' => $data['status'] ?? $appointment->status
            ]);

            if (isset($data['service_ids'])) {
                $appointment->services()->detach();
                foreach ($data['service_ids'] as $serviceId) {
                    $service = Service::active()->findOrFail($serviceId);
                    $appointment->services()->attach($serviceId, [
                        'price' => $service->price,
                        'duration' => $service->duration_minutes
                    ]);
                }
            }

            if (isset($data['products'])) {
                $appointment->products()->detach();
                foreach ($data['products'] as $productData) {
                    $product = Product::active()->inStock()->findOrFail($productData['id']);

                    if ($product->stock_quantity < $productData['quantity']) {
                        throw ValidationException::withMessages([
                            'products' => "Produto {$product->name} não tem estoque suficiente."
                        ]);
                    }

                    $appointment->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price
                    ]);

                    $product->decreaseStock($productData['quantity']);
                }
            }

            return $appointment->load(['barber', 'client', 'barbershop', 'services', 'products']);
        });
    }

    public function isBarberAvailable($barberId, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $barber = Barber::find($barberId);
        if (!$barber) {
            return false;
        }

        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);

        $workingDays = json_decode($barber->working_days, true) ?? [];
        $dayOfWeek = $startTime->dayOfWeek;

        if (!in_array($dayOfWeek, $workingDays)) {
            return false;
        }

        $barberStart = Carbon::parse($barber->start_time);
        $barberEnd = Carbon::parse($barber->end_time);

        $barberStart->setDate($startTime->year, $startTime->month, $startTime->day);
        $barberEnd->setDate($startTime->year, $startTime->month, $startTime->day);

        if (!$startTime->between($barberStart, $barberEnd) || 
            !$endTime->between($barberStart, $barberEnd)) {
            return false;
        }

        $query = Appointment::where('barber_id', $barberId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime->copy()->subSecond()])
                  ->orWhereBetween('end_time', [$startTime->copy()->addSecond(), $endTime])
                  ->orWhere(function ($q) use ($startTime, $endTime) {
                      $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return $query->count() === 0;
    }

    public function isWithinBusinessHours($barberId, $startTime, $endTime)
    {
        $barber = Barber::find($barberId);
        if (!$barber || !$barber->barbershop) {
            return false;
        }

        $barbershop = $barber->barbershop;
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);

        $openingTime = Carbon::parse($barbershop->opening_time);
        $closingTime = Carbon::parse($barbershop->closing_time);

        $openingTime->setDate($startTime->year, $startTime->month, $startTime->day);
        $closingTime->setDate($startTime->year, $startTime->month, $startTime->day);

        return $startTime->between($openingTime, $closingTime) && 
               $endTime->between($openingTime, $closingTime);
    }

    public function getAvailableSlots($barberId, $date)
    {
        $barber = Barber::findOrFail($barberId);
        if (!$barber->barbershop) {
            return [];
        }

        $barbershop = $barber->barbershop;
        $targetDate = Carbon::parse($date);

        $workingDays = json_decode($barber->working_days, true) ?? [];
        if (!in_array($targetDate->dayOfWeek, $workingDays)) {
            return [];
        }

        $openingTime = Carbon::parse($barbershop->opening_time);
        $closingTime = Carbon::parse($barbershop->closing_time);

        $openingTime->setDate($targetDate->year, $targetDate->month, $targetDate->day);
        $closingTime->setDate($targetDate->year, $targetDate->month, $targetDate->day);

        $interval = 30;
        $slots = [];
        $currentTime = $openingTime->copy();

        while ($currentTime->lessThan($closingTime)) {
            $slotEnd = $currentTime->copy()->addMinutes($interval);

            if ($slotEnd->lessThanOrEqualTo($closingTime)) {
                $slots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'available' => $this->isBarberAvailable($barberId, $currentTime, $slotEnd)
                ];
            }

            $currentTime->addMinutes($interval);
        }

        return $slots;
    }

    public function cancelAppointment(Appointment $appointment, $reason = null)
    {
        if ($appointment->status === 'cancelled') {
            throw ValidationException::withMessages([
                'appointment' => 'Este agendamento já foi cancelado.'
            ]);
        }

        foreach ($appointment->products as $product) {
            $product->increaseStock($product->pivot->quantity);
        }

        $appointment->update([
            'status' => 'cancelled',
            'notes' => $appointment->notes . "\nCancelado: " . ($reason ?? 'Sem motivo informado')
        ]);

        return $appointment;
    }

    public function completeAppointment(Appointment $appointment)
    {
        if ($appointment->status === 'completed') {
            throw ValidationException::withMessages([
                'appointment' => 'Este agendamento já foi finalizado.'
            ]);
        }

        $appointment->update([
            'status' => 'completed'
        ]);

        return $appointment;
    }
}