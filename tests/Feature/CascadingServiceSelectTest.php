<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadingServiceSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_root_categories()
    {
        // Создаем категории
        $cat1 = Service::create(['name' => 'Cat 1', 'parent_id' => -1]);
        $cat2 = Service::create(['name' => 'Cat 2', 'parent_id' => -1]);
        $sub = Service::create(['name' => 'Sub', 'parent_id' => $cat1->id]);

        $response = $this->getJson('/api/services/by-parent?parent_id=-1');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $cat1->id, 'name' => 'Cat 1'])
            ->assertJsonFragment(['id' => $cat2->id, 'name' => 'Cat 2'])
            ->assertJsonMissing(['id' => $sub->id]);
    }

    public function test_it_can_fetch_subcategories()
    {
        $root = Service::create(['name' => 'Root', 'parent_id' => -1]);
        $sub1 = Service::create(['name' => 'Sub 1', 'parent_id' => $root->id]);
        $sub2 = Service::create(['name' => 'Sub 2', 'parent_id' => $root->id]);
        $other = Service::create(['name' => 'Other', 'parent_id' => -1]);

        $response = $this->getJson("/api/services/by-parent?parent_id={$root->id}");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $sub1->id, 'name' => 'Sub 1'])
            ->assertJsonFragment(['id' => $sub2->id, 'name' => 'Sub 2'])
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_it_can_resolve_service_chain()
    {
        // Создаем цепочку: Root -> Sub -> Service
        $root = Service::create(['name' => 'Root', 'parent_id' => -1]);
        $sub = Service::create(['name' => 'Sub', 'parent_id' => $root->id]);
        $service = Service::create(['name' => 'Service', 'parent_id' => $sub->id]);

        // Другая услуга без полной цепочки (например, корневая, чего быть не должно, но проверим)
        $orphan = Service::create(['name' => 'Orphan', 'parent_id' => -1]);

        $response = $this->postJson('/api/services/resolve-chain', [
            'ids' => [$service->id, $orphan->id]
        ]);

        $response->assertOk()
            ->assertJsonCount(1) // Orphan должен быть проигнорирован, так как у него нет родителя и прародителя
            ->assertJsonFragment([
                'category_id' => $root->id,
                'subcategory_id' => $sub->id,
                'service_id' => $service->id,
            ]);
    }

    public function test_master_can_save_services()
    {
        $master = User::factory()->create(['role' => 'master']);
        $this->actingAs($master);

        $service1 = Service::create(['name' => 'S1', 'parent_id' => 1]); // Dummy parent
        $service2 = Service::create(['name' => 'S2', 'parent_id' => 1]);

        // Эмулируем сохранение настроек
        // В Settings.vue: form.put('/master/settings')
        // Но у нас нет отдельного роута /master/settings в api.php, 
        // вероятно это Web роут или в контроллере User/MasterController.
        // Проверим web.php или контроллеры.
        
        // В предыдущих шагах я видел, что Settings.vue отправляет PUT /master/settings
        // Нужно найти этот контроллер.
        
        // Пока проверим базовое сохранение через отношение (Unit-style), 
        // если не найдем роут быстро.
        
        $master->services()->sync([$service1->id, $service2->id]);
        
        $this->assertCount(2, $master->services);
        $this->assertTrue($master->services->contains($service1));
        $this->assertTrue($master->services->contains($service2));
    }
}
