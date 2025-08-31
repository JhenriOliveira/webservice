<?php
// app/Http/Controllers/ClientController.php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Client::with('user');
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        if ($request->has(['latitude', 'longitude'])) {
            $radius = $request->radius ?? 10;
            $query->nearby($request->latitude, $request->longitude, $radius);
        }
        
        $clients = $query->paginate(10);
        
        return response()->json($clients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:clients,user_id',
            'cpf' => 'required|string|unique:clients,cpf',
            'phone' => 'required|string',
            'phone_secondary' => 'nullable|string',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'zip_code' => 'nullable|string|max:10',
            'street' => 'nullable|string|max:200',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'country' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
            'preferences' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medical_conditions' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);

            $client = Client::create([
                'user_id' => $request->user_id,
                'cpf' => $request->cpf,
                'phone' => $request->phone,
                'phone_secondary' => $request->phone_secondary,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'zip_code' => $request->zip_code,
                'street' => $request->street,
                'number' => $request->number,
                'complement' => $request->complement,
                'neighborhood' => $request->neighborhood,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                
                'preferences' => $request->preferences,
                'allergies' => $request->allergies,
                'medical_conditions' => $request->medical_conditions
            ]);

            return response()->json([
                'message' => 'Cliente criado com sucesso',
                'data' => $client->load('user')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $client = Client::with('user')->findOrFail($id);
        
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $client = Client::with('user')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cpf' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('clients', 'cpf')->ignore($client->id)
            ],
            'phone' => 'sometimes|required|string',
            'phone_secondary' => 'nullable|string',
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|in:male,female,other',
            'zip_code' => 'nullable|string|max:10',
            'street' => 'nullable|string|max:200',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'country' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
            'preferences' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medical_conditions' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $client->update($request->all());

            return response()->json([
                'message' => 'Cliente atualizado com sucesso',
                'data' => $client->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        
        try {
            $client->delete();

            return response()->json([
                'message' => 'Cliente deletado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao deletar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Métodos adicionais (não padrão do apiResource)
     */
    
    public function getByUserId($userId)
    {
        $client = Client::with('user')->where('user_id', $userId)->firstOrFail();
        
        return response()->json($client);
    }

    public function updateLoyaltyPoints(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'points' => 'required|integer',
            'action' => 'required|in:add,subtract,set'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            switch ($request->action) {
                case 'add':
                    $client->loyalty_points += $request->points;
                    break;
                case 'subtract':
                    $client->loyalty_points = max(0, $client->loyalty_points - $request->points);
                    break;
                case 'set':
                    $client->loyalty_points = max(0, $request->points);
                    break;
            }

            $client->save();

            return response()->json([
                'message' => 'Pontos de fidelidade atualizados',
                'loyalty_points' => $client->loyalty_points
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar pontos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function nearbyClients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $radius = $request->radius ?? 10;
        $clients = Client::with('user')
            ->nearby($request->latitude, $request->longitude, $radius)
            ->get();

        return response()->json([
            'data' => $clients,
            'radius_km' => $radius
        ]);
    }
}