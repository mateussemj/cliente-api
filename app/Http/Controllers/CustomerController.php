<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Interfaces\AddressProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $customers = Customer::latest()->paginate(10);
        
        $customers->getCollection()->transform(function ($customer) {
            $customer->endereco_formatado = $customer->full_address;
            return $customer;
        });

        return response()->json($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request, AddressProviderInterface $addressProvider): JsonResponse
    {
        $data = $request->validated();

        $address = $addressProvider->getAddressByCep($data['cep']);

        if (!$address) {
            return response()->json(['error' => 'CEP inválido ou não encontrado.'], 422);
        }

        try {
            $customer = DB::transaction(function () use ($data, $address) {
                $customerData = array_merge($data, $address);
                return Customer::create($customerData);
            });

            return response()->json($customer, 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno ao salvar o cliente.'], 500);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->endereco_formatado = $customer->full_address;
        
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer, AddressProviderInterface $addressProvider): JsonResponse
    {
        $data = $request->validated();

        if (empty($data)) {
            return response()->json([
                'error' => 'Nenhum dado válido foi fornecido para atualização.',
                'dica' => 'Os campos aceitos são: name, email, document e cep.'
            ], 400);
        }

        if (isset($data['cep']) && $data['cep'] !== $customer->cep) {
            $address = $addressProvider->getAddressByCep($data['cep']);

            if (!$address) {
                return response()->json(['error' => 'CEP inválido ou não encontrado.'], 422);
            }

            $data = array_merge($data, $address);
        }

        try {
            DB::transaction(function () use ($customer, $data) {
                $customer->update($data);
            });

            $customer->endereco_formatado = $customer->full_address;

            return response()->json($customer);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno ao atualizar o cliente.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->delete();
            
            return response()->json(null, 204); 
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno ao deletar o cliente.'], 500);
        }
    }
}
