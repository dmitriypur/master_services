<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Client\CreateClient;
use App\Actions\Client\UpdateClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\StoreClientRequest;
use App\Http\Requests\Api\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = Auth::user();
        $clients = $user->clients()->orderBy('name')->get();

        return ClientResource::collection($clients);
    }

    public function store(StoreClientRequest $request, CreateClient $action): JsonResource
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $client = $action->execute($data);

        return new ClientResource($client);
    }

    public function show(Client $client): JsonResource
    {
        abort_unless($client->user_id === Auth::id(), 404);

        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClient $action): JsonResource
    {
        abort_unless($client->user_id === Auth::id(), 404);
        $client = $action->execute($client, $request->validated());

        return new ClientResource($client);
    }
}
