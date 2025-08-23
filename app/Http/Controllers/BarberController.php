<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BarberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Barber::with(['user:id,name,email', 'barbershop:id,name'])
                ->whereNull('deleted_at');

            // Filtro por barbearia
            if ($request->has('barbershop_id')) {
                $query->where('barbershop_id', $request->barbershop_id);
            }

            // Filtro por status
            if ($request->has('active')) {
                $query->where('active', $request->active);
            }

            $barbers = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $barbers,
                'count' => $barbers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbeiros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'barbershop_id' => 'required|exists:barbershops,id',
                'name' => 'required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'required|string|max:15',
                'photo_base64' => 'nullable|string',
                'photo_type' => 'nullable|string|max:20|in:jpeg,jpg,png,gif,webp',
                'specialties' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:1000',
                'experience_years' => 'required|integer|min:0|max:100',
                'rating' => 'nullable|numeric|min:0|max:5',
                'active' => 'boolean',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'working_days' => 'required|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar se usuário já é barbeiro em outra barbearia
            $existingBarber = Barber::where('user_id', $request->user_id)
                ->whereNull('deleted_at')
                ->first();

            if ($existingBarber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuário já é barbeiro em outra barbearia'
                ], 422);
            }

            $barber = Barber::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Barbeiro cadastrado com sucesso',
                'data' => $barber->load(['user:id,name,email', 'barbershop:id,name'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cadastrar barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $barber = Barber::with(['user:id,name,email', 'barbershop:id,name'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $barber
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbeiro não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $barber = Barber::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'sometimes|required|string|max:15',
                'photo_base64' => 'nullable|string',
                'photo_type' => 'nullable|string|max:20|in:jpeg,jpg,png,gif,webp',
                'specialties' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:1000',
                'experience_years' => 'sometimes|required|integer|min:0|max:100',
                'rating' => 'nullable|numeric|min:0|max:5',
                'active' => 'boolean',
                'start_time' => 'sometimes|required|date_format:H:i',
                'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
                'working_days' => 'sometimes|required|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barber->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Barbeiro atualizado com sucesso',
                'data' => $barber->fresh(['user:id,name,email', 'barbershop:id,name'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbeiro não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $barber = Barber::findOrFail($id);
            $barber->delete();

            return response()->json([
                'success' => true,
                'message' => 'Barbeiro excluído com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbeiro não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active barbers only
     */
    public function active()
    {
        try {
            $barbers = Barber::with(['user:id,name,email', 'barbershop:id,name'])
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbers,
                'count' => $barbers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbeiros ativos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inactive barbers only
     */
    public function inactive()
    {
        try {
            $barbers = Barber::with(['user:id,name,email', 'barbershop:id,name'])
                ->where('active', 0)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbers,
                'count' => $barbers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbeiros inativos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get barbers by barbershop
     */
    public function byBarbershop($barbershopId)
    {
        try {
            $barbers = Barber::with(['user:id,name,email', 'barbershop:id,name'])
                ->where('barbershop_id', $barbershopId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbers,
                'count' => $barbers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbeiros da barbearia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted barber.
     */
    public function restore(string $id)
    {
        try {
            $barber = Barber::onlyTrashed()->findOrFail($id);
            $barber->restore();

            return response()->json([
                'success' => true,
                'message' => 'Barbeiro restaurado com sucesso',
                'data' => $barber->load(['user:id,name,email', 'barbershop:id,name'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbeiro não encontrado na lixeira'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trashed barbers.
     */
    public function trashed()
    {
        try {
            $barbers = Barber::onlyTrashed()
                ->with(['user:id,name,email', 'barbershop:id,name'])
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbers,
                'count' => $barbers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbeiros excluídos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(string $id)
    {
        try {
            $barber = Barber::findOrFail($id);
            $barber->active = $barber->active == 1 ? 0 : 1;
            $barber->save();

            return response()->json([
                'success' => true,
                'message' => 'Status do barbeiro alterado com sucesso',
                'data' => [
                    'id' => $barber->id,
                    'active' => $barber->active
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbeiro não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}