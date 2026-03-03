<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Interfaces\AddressProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "API de Clientes",
    description: "Documentação interativa do nosso CRUD de clientes com integração de CEP."
)]
#[OA\Server(url: "http://localhost:8000")]
class CustomerController extends Controller
{
    #[OA\Get(
        path: "/api/customers",
        summary: "Lista todos os clientes",
        tags: ["Clientes"]
    )]
    #[OA\Response(response: 200, description: "Lista de clientes retornada com sucesso")]
    public function index(): JsonResponse
    {
        $customers = Customer::latest()->paginate(10);
        
        $customers->getCollection()->transform(function ($customer) {
            $customer->endereco_formatado = $customer->full_address;
            return $customer;
        });

        return response()->json($customers);
    }

    #[OA\Post(
        path: "/api/customers",
        summary: "Cadastra um novo cliente",
        tags: ["Clientes"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "document", "cep"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "João da Silva"),
                new OA\Property(property: "email", type: "string", example: "joao@email.com"),
                new OA\Property(property: "document", type: "string", example: "12345678901"),
                new OA\Property(property: "cep", type: "string", example: "01001000")
            ]
        )
    )]
    #[OA\Response(response: 201, description: "Cliente criado com sucesso")]
    #[OA\Response(response: 422, description: "Erro de validação ou CEP inválido")]
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
    
    #[OA\Get(
        path: "/api/customers/{customer}",
        summary: "Busca os detalhes de um cliente específico",
        tags: ["Clientes"]
    )]
    #[OA\Parameter(
        name: "customer",
        description: "ID do Cliente",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 200, description: "Detalhes do cliente retornados com sucesso")]
    #[OA\Response(response: 404, description: "Cliente não encontrado")]
    public function show(Customer $customer): JsonResponse
    {
        $customer->endereco_formatado = $customer->full_address;
        
        return response()->json($customer);
    }

    #[OA\Put(
        path: "/api/customers/{customer}",
        summary: "Atualiza os dados de um cliente",
        tags: ["Clientes"]
    )]
    #[OA\Parameter(
        name: "customer",
        description: "ID do Cliente",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string", example: "João Editado"),
                new OA\Property(property: "cep", type: "string", example: "81280120")
            ]
        )
    )]
    #[OA\Response(response: 200, description: "Cliente atualizado com sucesso")]
    #[OA\Response(response: 400, description: "Nenhum dado válido fornecido")]
    #[OA\Response(response: 422, description: "Erro de validação ou CEP inválido")]
    #[OA\Response(response: 404, description: "Cliente não encontrado")]
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

    #[OA\Delete(
        path: "/api/customers/{customer}",
        summary: "Remove um cliente do sistema",
        tags: ["Clientes"]
    )]
    #[OA\Parameter(
        name: "customer",
        description: "ID do Cliente",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 204, description: "Cliente deletado com sucesso (Sem conteúdo)")]
    #[OA\Response(response: 404, description: "Cliente não encontrado")]
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