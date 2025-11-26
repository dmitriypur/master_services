<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Models\Client;

class CreateClient
{
    public function execute(array $data): Client
    {
        return Client::query()->create($data);
    }
}
