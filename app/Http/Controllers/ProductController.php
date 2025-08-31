<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('barbershop');
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->outOfStock();
                    break;
            }
        }
        
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $products = $query->paginate($request->get('per_page', 15));
        
        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('barbershop'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barbershop_id' => 'required|exists:barbershops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'category' => 'required|string|in:haircare,beardcare,skincare,styling,tools,accessories',
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'ingredients' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image_url'] = Storage::url($imagePath);
            }

            $product = Product::create($data);
            
            return response()->json([
                'message' => 'Produto criado com sucesso',
                'data' => $product->load('barbershop')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'min_stock' => 'sometimes|required|integer|min:0',
            'category' => 'sometimes|required|string|in:haircare,beardcare,skincare,styling,tools,accessories',
            'brand' => 'nullable|string|max:255',
            'sku' => [
                'nullable',
                'string',
                Rule::unique('products', 'sku')->ignore($product->id)
            ],
            'ingredients' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            
            if ($request->hasFile('image')) {
                if ($product->image_url) {
                    $oldImage = str_replace('/storage/', '', $product->image_url);
                    Storage::disk('public')->delete($oldImage);
                }
                
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image_url'] = Storage::url($imagePath);
            }

            $product->update($data);
            
            return response()->json([
                'message' => 'Produto atualizado com sucesso',
                'data' => $product->load('barbershop')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            if ($product->image_url) {
                $imagePath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $product->delete();
            
            return response()->json([
                'message' => 'Produto deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao deletar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStock(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'action' => 'required|in:add,subtract,set'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            switch ($request->action) {
                case 'add':
                    $product->increaseStock($request->quantity);
                    break;
                case 'subtract':
                    $product->decreaseStock($request->quantity);
                    break;
                case 'set':
                    $product->stock_quantity = max(0, $request->quantity);
                    $product->save();
                    break;
            }

            return response()->json([
                'message' => 'Estoque atualizado com sucesso',
                'stock_quantity' => $product->stock_quantity,
                'stock_status' => $product->stock_status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar estoque: ' . $e->getMessage()
            ], 500);
        }
    }

    public function categories()
    {
        $categories = [
            'haircare' => 'Cuidados com o Cabelo',
            'beardcare' => 'Cuidados com a Barba',
            'skincare' => 'Cuidados com a Pele',
            'styling' => 'Estilização',
            'tools' => 'Ferramentas',
            'accessories' => 'Acessórios'
        ];
        
        return response()->json($categories);
    }

    public function lowStockReport(Request $request)
    {
        $query = Product::with('barbershop')->lowStock();
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        $products = $query->get();
        
        return response()->json([
            'total_low_stock' => $products->count(),
            'products' => $products
        ]);
    }

    public function outOfStockReport(Request $request)
    {
        $query = Product::with('barbershop')->outOfStock();
        
        if ($request->has('barbershop_id')) {
            $query->where('barbershop_id', $request->barbershop_id);
        }
        
        $products = $query->get();
        
        return response()->json([
            'total_out_of_stock' => $products->count(),
            'products' => $products
        ]);
    }
}