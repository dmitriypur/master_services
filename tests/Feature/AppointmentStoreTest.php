<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_create_appointment_with_new_client(): void
    {
        $service = Service::create(['name' => 'Haircut', 'parent_id' => -1]);

        $master = User::factory()->create(['role' => 'master']);
        $master->masterSettings()->create([
            'address' => 'Test',
            'work_days' => ['mon'],
            'work_time_from' => '09:00',
            'work_time_to' => '18:00',
            'slot_duration_min' => 30,
        ]);
        $master->services()->sync([$service->id => ['is_active' => true]]);

        $this->actingAs($master);

        $this->app->instance(SlotService::class, new class extends SlotService
        {
            public function isFree($master, $startsAt, $endsAt): bool
            {
                return true;
            }
        });

        $payload = [
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'service_id' => $service->id,
            'client_name' => 'John Doe',
            'client_phone' => '79991234567',
            'preferred_channels' => ['phone'],
        ];

        $response = $this->postJson('/api/appointments', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'master_id', 'client_id', 'service_id', 'starts_at', 'ends_at', 'status', 'source',
                ],
            ])
            ->assertJsonPath('data.master_id', $master->id)
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.source', 'manual');
    }
}
