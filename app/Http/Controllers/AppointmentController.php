<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Product;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    protected $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products']);
        
        if ($request->has('barber_id')) {
            $query->where('barber_id', $request->barber_id);
        }
        
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }
        
        $order = $request->get('order', 'asc');
        $query->orderBy('start_time', $order);
        
        $appointments = $query->paginate($request->get('per_page', 15));
        
        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barber_id' => 'required|exists:barbers,id',
            'client_id' => 'required|exists:clients,id',
            'barbershop_id' => 'required|exists:barbershops,id',
            'start_time' => 'required|date',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'products' => 'sometimes|array',
            'products.*.id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $appointment = $this->appointmentService->createAppointment($request->all());
            
            return response()->json([
                'message' => 'Agendamento criado com sucesso',
                'data' => $appointment->load(['barber', 'client', 'barbershop', 'services', 'products'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar agendamento: ' . $e->getMessage()], 400);
        }
    }

    public function show(Appointment $appointment)
    {
        return response()->json($appointment->load(['barber', 'client', 'barbershop', 'services', 'products']));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'sometimes|date',
            'service_ids' => 'sometimes|array',
            'service_ids.*' => 'exists:services,id',
            'products' => 'sometimes|array',
            'products.*.id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->has('start_time') || $request->has('service_ids') || $request->has('products')) {
                $appointment = $this->appointmentService->updateAppointment($appointment, $request->all());
            } else {
                $appointment->update($request->only(['notes', 'status']));
            }
            
            return response()->json([
                'message' => 'Agendamento atualizado com sucesso',
                'data' => $appointment->load(['barber', 'client', 'barbershop', 'services', 'products'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar agendamento: ' . $e->getMessage()], 400);
        }
    }

    public function destroy(Appointment $appointment)
    {
        try {
            $this->appointmentService->cancelAppointment($appointment, 'Agendamento removido pelo sistema');
            
            return response()->json([
                'message' => 'Agendamento cancelado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao cancelar agendamento: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(Appointment $appointment, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $appointment = $this->appointmentService->cancelAppointment($appointment, $request->reason);
            
            return response()->json([
                'message' => 'Agendamento cancelado com sucesso',
                'data' => $appointment
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao cancelar agendamento: ' . $e->getMessage()], 400);
        }
    }

    public function confirm(Appointment $appointment)
    {
        try {
            if ($appointment->status === 'confirmed') {
                return response()->json([
                    'message' => 'Agendamento já está confirmado'
                ], 400);
            }

            $appointment->update(['status' => 'confirmed']);
            
            return response()->json([
                'message' => 'Agendamento confirmado com sucesso',
                'data' => $appointment->load(['barber', 'client', 'barbershop', 'services', 'products'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao confirmar agendamento: ' . $e->getMessage()], 400);
        }
    }

    public function complete(Appointment $appointment)
    {
        try {
            $appointment = $this->appointmentService->completeAppointment($appointment);
            
            return response()->json([
                'message' => 'Agendamento marcado como concluído',
                'data' => $appointment->load(['barber', 'client', 'barbershop', 'services', 'products'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao finalizar agendamento: ' . $e->getMessage()], 400);
        }
    }

    public function availableSlots($barberId, $date)
    {
        $validator = Validator::make(['date' => $date], [
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $slots = $this->appointmentService->getAvailableSlots($barberId, $date);
            
            return response()->json(['data' => $slots]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar horários disponíveis: ' . $e->getMessage()], 400);
        }
    }

    public function upcoming(Request $request)
    {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products'])
            ->upcoming();
        
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        if ($request->has('barber_id')) {
            $query->where('barber_id', $request->barber_id);
        }
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        $appointments = $query->paginate($request->get('per_page', 15));
        
        return response()->json($appointments);
    }

    public function history(Request $request)
    {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products'])
            ->history();
        
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        if ($request->has('barber_id')) {
            $query->where('barber_id', $request->barber_id);
        }
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        $appointments = $query->paginate($request->get('per_page', 15));
        
        return response()->json($appointments);
    }

    public function services()
    {
        $services = Service::active()->get();
        return response()->json($services);
    }

    public function products()
    {
        $products = Product::active()->inStock()->get();
        return response()->json($products);
    }

/**
 * Obter todos os agendamentos de um cliente específico
 */
public function byClient($clientId, Request $request)
{
    $validator = Validator::make(['client_id' => $clientId], [
        'client_id' => 'required|exists:clients,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products'])
            ->where('client_id', $clientId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $order = $request->get('order', 'desc'); 
        $query->orderBy('start_time', $order);

        $appointments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'client_id' => $clientId,
            'total_appointments' => $appointments->total(),
            'data' => $appointments
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar agendamentos: ' . $e->getMessage()], 500);
    }
}

/**
 * Obter todos os agendamentos de um barbeiro específico
 */
public function byBarber($barberId, Request $request)
{
    $validator = Validator::make(['barber_id' => $barberId], [
        'barber_id' => 'required|exists:barbers,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products'])
            ->where('barber_id', $barberId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $order = $request->get('order', 'desc');
        $query->orderBy('start_time', $order);

        $appointments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'barber_id' => $barberId,
            'total_appointments' => $appointments->total(),
            'data' => $appointments
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar agendamentos: ' . $e->getMessage()], 500);
    }
}

/**
 * Obter todos os agendamentos de uma barbearia específica
 */
public function byBarbershop($barbershopId, Request $request)
{
    $validator = Validator::make(['barbershop_id' => $barbershopId], [
        'barbershop_id' => 'required|exists:barbershops,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $query = Appointment::with(['barber', 'client', 'barbershop', 'services', 'products'])
            ->where('barbershop_id', $barbershopId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $order = $request->get('order', 'desc');
        $query->orderBy('start_time', $order);

        $appointments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'barbershop_id' => $barbershopId,
            'total_appointments' => $appointments->total(),
            'data' => $appointments
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao buscar agendamentos: ' . $e->getMessage()], 500);
    }
}
}