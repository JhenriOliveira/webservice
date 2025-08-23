<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    protected $appointmentService;
    
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barber_id' => 'required|exists:barbers,id',
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
            'start_time' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string'
        ]);
        
        try {
            $appointment = $this->appointmentService->createAppointment($validated);
            
            return response()->json([
                'message' => 'Agendamento criado com sucesso',
                'data' => $appointment
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar agendamento: ' . $e->getMessage()
            ], 400);
        }
    }
    
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barber_id' => 'required|exists:barbers,id',
            'date' => 'required|date',
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id'
        ]);
        
        $slots = $this->appointmentService->getAvailableSlots(
            $validated['barber_id'],
            $validated['date'],
            $validated['service_ids']
        );
        
        return response()->json(['slots' => $slots]);
    }
}