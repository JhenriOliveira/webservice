<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barbershop_id' => 'nullable|exists:barbershops,id',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Service::with('barbershop:id,name,user_id');

            if ($request->has('barbershop_id') && $request->barbershop_id) {
                $query->where('barbershop_id', $request->barbershop_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            } else {
                $query->where('is_active', true);
            }

            $services = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços',
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
                'barbershop_id' => 'required|exists:barbershops,id',
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('services')
                        ->where('barbershop_id', $request->barbershop_id)
                        ->whereNull('deleted_at')
                ],
                'description' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0|max:9999.99',
                'duration_minutes' => 'required|integer|min:1|max:480',
                'is_is_active' => 'boolean'
            ], [
                'name.unique' => 'Já existe um serviço com este nome nesta barbearia',
                'price.max' => 'O preço não pode ser superior a R$ 9.999,99',
                'duration_minutes.max' => 'A duração não pode ser superior a 8 horas (480 minutos)'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barbershop = Barbershop::findOrFail($request->barbershop_id);
            
            if ($barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para criar serviços nesta barbearia'
                ], 403);
            }

            $service = Service::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Serviço criado com sucesso',
                'data' => $service->load('barbershop:id,name')
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar serviço',
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
            $service = Service::with('barbershop:id,name,user_id')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $service
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serviço não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar serviço',
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
            $service = Service::with('barbershop')->findOrFail($id);

            if ($service->barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este serviço'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('services')
                        ->where('barbershop_id', $service->barbershop_id)
                        ->ignore($service->id)
                        ->whereNull('deleted_at')
                ],
                'description' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0|max:9999.99',
                'duration_minutes' => 'sometimes|required|integer|min:1|max:480',
                'is_active' => 'sometimes|boolean'
            ], [
                'name.unique' => 'Já existe um serviço com este nome nesta barbearia',
                'price.max' => 'O preço não pode ser superior a R$ 9.999,99',
                'duration_minutes.max' => 'A duração não pode ser superior a 8 horas (480 minutos)'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Serviço atualizado com sucesso',
                'data' => $service->fresh(['barbershop:id,name'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serviço não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar serviço',
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
            $service = Service::with('barbershop')->findOrFail($id);

            if ($service->barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este serviço'
                ], 403);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Serviço excluído com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serviço não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir serviço',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted service.
     */
    public function restore(string $id)
    {
        try {
            $service = Service::onlyTrashed()->with('barbershop')->findOrFail($id);

            if ($service->barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para restaurar este serviço'
                ], 403);
            }

            $service->restore();

            return response()->json([
                'success' => true,
                'message' => 'Serviço restaurado com sucesso',
                'data' => $service->load('barbershop:id,name')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serviço não encontrado na lixeira'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar serviço',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trashed services.
     */
    public function trashed(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barbershop_id' => 'nullable|exists:barbershops,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Service::onlyTrashed()
                ->with('barbershop:id,name,user_id');

            if ($request->has('barbershop_id') && $request->barbershop_id) {
                $query->where('barbershop_id', $request->barbershop_id);
            }

            $services = $query->orderBy('deleted_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços excluídos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle is_active status.
     */
    public function toggleStatus(string $id)
    {
        try {
            $service = Service::with('barbershop')->findOrFail($id);

            if ($service->barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para alterar o status deste serviço'
                ], 403);
            }

            $service->is_active = !$service->is_active;
            $service->save();

            return response()->json([
                'success' => true,
                'message' => 'Status do serviço alterado com sucesso',
                'data' => [
                    'id' => $service->id,
                    'is_active' => $service->is_active
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Serviço não encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do serviço',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get is_active services only.
     */
    public function is_active(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barbershop_id' => 'nullable|exists:barbershops,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Service::with('barbershop:id,name,user_id')
                ->where('is_active', true)
                ->whereNull('deleted_at');

            if ($request->has('barbershop_id') && $request->barbershop_id) {
                $query->where('barbershop_id', $request->barbershop_id);
            }

            $services = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços ativos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inis_active services only.
     */
    public function inis_active(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barbershop_id' => 'nullable|exists:barbershops,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Service::with('barbershop:id,name,user_id')
                ->where('is_active', false)
                ->whereNull('deleted_at');

            if ($request->has('barbershop_id') && $request->barbershop_id) {
                $query->where('barbershop_id', $request->barbershop_id);
            }

            $services = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços inativos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services by barber (serviços que um barbeiro específico pode realizar)
     */
    public function byBarber(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barber_id' => 'required|exists:barbers,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $services = Service::with('barbershop:id,name')
                ->where('barbershop_id', function($query) use ($request) {
                    $query->select('barbershop_id')
                        ->from('barbers')
                        ->where('id', $request->barber_id);
                })
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços do barbeiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all services (including inis_active) for admin management.
     */
    public function allForManagement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barbershop_id' => 'required|exists:barbershops,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barbershop = Barbershop::findOrFail($request->barbershop_id);
            if ($barbershop->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar estes serviços'
                ], 403);
            }

            $services = Service::withTrashed()
                ->with('barbershop:id,name')
                ->where('barbershop_id', $request->barbershop_id)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar serviços para gerenciamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}