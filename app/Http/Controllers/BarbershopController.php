<?php

namespace App\Http\Controllers;

use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BarbershopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $barbershops = Barbershop::with('user:id,name,email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbershops,
                'count' => $barbershops->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbearias',
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
                'name' => 'required|string|max:100',
                'corporate_name' => 'nullable|string|max:150',
                'tax_id' => [
                    'nullable',
                    'string',
                    'max:18',
                    'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/',
                    Rule::unique('barbershops')->whereNull('deleted_at')
                ],
                'phone' => 'required|string|max:15',
                'email' => [
                    'required',
                    'email',
                    'max:100',
                    Rule::unique('barbershops')->whereNull('deleted_at')
                ],
                'description' => 'nullable|string|max:500',
                'address' => 'required|string|max:200',
                'address_number' => 'required|string|max:10',
                'address_complement' => 'nullable|string|max:100',
                'neighborhood' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'state' => 'required|string|size:2',
                'zip_code' => 'required|string|max:9|regex:/^\d{5}-\d{3}$/',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i|after:opening_time',
                'average_service_time' => 'required|integer|min:5|max:240',
                'accepts_online_scheduling' => 'boolean',
                'active' => 'boolean',
                'profile_photo_base64' => 'nullable|string',
                'profile_photo_type' => 'nullable|string|max:20|in:jpeg,jpg,png,gif,webp',
                'social_media' => 'nullable|json',
                'working_days' => 'required|json'
            ], [
                'tax_id.regex' => 'O CNPJ deve estar no formato 99.999.999/9999-99',
                'zip_code.regex' => 'O CEP deve estar no formato 99999-999',
                'closing_time.after' => 'O horário de fechamento deve ser após o horário de abertura',
                'profile_photo_type.in' => 'Formato de imagem não suportado. Use: jpeg, jpg, png, gif ou webp'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar se o usuário já tem uma barbearia
            $existingBarbershop = Barbershop::where('user_id', $request->user_id)
                ->whereNull('deleted_at')
                ->first();

            if ($existingBarbershop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuário já possui uma barbearia cadastrada'
                ], 422);
            }

            $barbershop = Barbershop::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Barbearia cadastrada com sucesso',
                'data' => $barbershop->load('user:id,name,email')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cadastrar barbearia',
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
            $barbershop = Barbershop::with('user:id,name,email')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $barbershop
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar barbearia',
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
            $barbershop = Barbershop::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'corporate_name' => 'nullable|string|max:150',
                'tax_id' => [
                    'nullable',
                    'string',
                    'max:18',
                    'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/',
                    Rule::unique('barbershops')
                        ->ignore($barbershop->id)
                        ->whereNull('deleted_at')
                ],
                'phone' => 'sometimes|required|string|max:15',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:100',
                    Rule::unique('barbershops')
                        ->ignore($barbershop->id)
                        ->whereNull('deleted_at')
                ],
                'description' => 'nullable|string|max:500',
                'address' => 'sometimes|required|string|max:200',
                'address_number' => 'sometimes|required|string|max:10',
                'address_complement' => 'nullable|string|max:100',
                'neighborhood' => 'sometimes|required|string|max:100',
                'city' => 'sometimes|required|string|max:100',
                'state' => 'sometimes|required|string|size:2',
                'zip_code' => 'sometimes|required|string|max:9|regex:/^\d{5}-\d{3}$/',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'opening_time' => 'sometimes|required|date_format:H:i',
                'closing_time' => 'sometimes|required|date_format:H:i|after:opening_time',
                'average_service_time' => 'sometimes|required|integer|min:5|max:240',
                'accepts_online_scheduling' => 'boolean',
                'active' => 'boolean',
                'profile_photo_base64' => 'nullable|string',
                'profile_photo_type' => 'nullable|string|max:20|in:jpeg,jpg,png,gif,webp',
                'social_media' => 'nullable|json',
                'working_days' => 'sometimes|required|json'
            ], [
                'tax_id.regex' => 'O CNPJ deve estar no formato 99.999.999/9999-99',
                'zip_code.regex' => 'O CEP deve estar no formato 99999-999',
                'closing_time.after' => 'O horário de fechamento deve ser após o horário de abertura',
                'profile_photo_type.in' => 'Formato de imagem não suportado. Use: jpeg, jpg, png, gif ou webp'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barbershop->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Barbearia atualizada com sucesso',
                'data' => $barbershop->fresh('user:id,name,email')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar barbearia',
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
            $barbershop = Barbershop::findOrFail($id);
            $barbershop->delete();

            return response()->json([
                'success' => true,
                'message' => 'Barbearia excluída com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir barbearia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft deleted barbershop.
     */
    public function restore(string $id)
    {
        try {
            $barbershop = Barbershop::onlyTrashed()->findOrFail($id);
            $barbershop->restore();

            return response()->json([
                'success' => true,
                'message' => 'Barbearia restaurada com sucesso',
                'data' => $barbershop->load('user:id,name,email')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada na lixeira'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar barbearia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trashed barbershops.
     */
    public function trashed()
    {
        try {
            $barbershops = Barbershop::onlyTrashed()
                ->with('user:id,name,email')
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbershops,
                'count' => $barbershops->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbearias excluídas',
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
            $barbershop = Barbershop::findOrFail($id);
            $barbershop->active = !$barbershop->active;
            $barbershop->save();

            return response()->json([
                'success' => true,
                'message' => 'Status da barbearia alterado com sucesso',
                'data' => [
                    'id' => $barbershop->id,
                    'active' => $barbershop->active
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barbearia não encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status da barbearia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active barbershops only.
     */
    public function active()
    {
        try {
            $barbershops = Barbershop::with('user:id,name,email')
                ->where('active', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbershops,
                'count' => $barbershops->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbearias ativas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inactive barbershops only.
     */
    public function inactive()
    {
        try {
            $barbershops = Barbershop::with('user:id,name,email')
                ->where('active', false)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $barbershops,
                'count' => $barbershops->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar barbearias inativas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}