<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentShowAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_at_returns_appointment_in_slot(): void
    {
        $master = User::factory()->create(['role' => 'master']);
        $master->masterSettings()->create([
            'address' => 'Test',
            'work_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
            'work_time_from' => '09:00',
            'work_time_to' => '18:00',
            'slot_duration_min' => 30,
        ]);
        $service = Service::create(['name' => 'Haircut', 'parent_id' => -1]);
        $master->services()->sync([$service->id => ['is_active' => true]]);

        $date = now()->next('Monday')->toDateString();
        // Слот: 10:00–10:30
        $slotStart = $date.' 10:00:00';
        $slotEnd = $date.' 10:30:00';

        $appointment = Appointment::query()->create([
            'master_id' => $master->id,
            'client_id' => null,
            'service_id' => $service->id,
            'starts_at' => $slotStart,
            'ends_at' => $slotEnd,
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => 'manual',
        ]);

        $this->actingAs($master);

        $response = $this->getJson('/api/appointments/at?date='.$date.'&time=10:00');

        $response->assertOk()
            ->assertJsonPath('data.id', $appointment->id)
            ->assertJsonPath('data.master_id', $master->id)
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.status', 'scheduled');
    }
}
