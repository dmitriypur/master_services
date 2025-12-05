<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_redirects_to_master_calendar(): void
    {
        $user = User::factory()->create([
            'email' => 'master@example.com',
            'role' => 'master',
            // factory sets password to Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'email' => 'master@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/master/calendar');
        $this->assertAuthenticatedAs($user);
    }

    public function test_update_master_settings_persists_and_syncs_services(): void
    {
        $master = User::factory()->create(['role' => 'master']);
        $city = City::query()->create(['name' => 'Moscow', 'slug' => 'moscow', 'is_active' => true]);
        $serviceA = Service::create(['name' => 'A', 'parent_id' => -1]);
        $serviceB = Service::create(['name' => 'B', 'parent_id' => -1]);

        $this->actingAs($master);

        $payload = [
            'city_id' => $city->id,
            'address' => 'Test street 1',
            'phone' => '79990001122',
            'work_days' => ['mon','tue','wed'],
            'work_time_from' => '09:00',
            'work_time_to' => '18:00',
            'slot_duration_min' => 30,
            'services' => [$serviceA->id, $serviceB->id],
        ];

        $response = $this->put('/master/settings', $payload);
        $response->assertRedirect('/master/calendar');

        $master->refresh();
        $settings = $master->masterSettings;
        $this->assertNotNull($settings);
        $this->assertSame('Test street 1', $settings->address);
        $this->assertSame(['mon','tue','wed'], $settings->work_days);
        $this->assertSame('09:00', $settings->work_time_from);
        $this->assertSame('18:00', $settings->work_time_to);
        $this->assertSame(30, $settings->slot_duration_min);
        $this->assertSame($city->id, $master->city_id);

        $serviceIds = $master->services()->wherePivot('is_active', true)->pluck('services.id')->all();
        sort($serviceIds);
        $this->assertSame([$serviceA->id, $serviceB->id], $serviceIds);
    }
}

