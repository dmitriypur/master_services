<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Models\Client;

class UpdateClient
{
    public function execute(Client $client, array $data): Client
    {
        $client->fill($data);
        $client->save();

        return $client->refresh();
    }
}
