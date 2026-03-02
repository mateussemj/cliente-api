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
}
