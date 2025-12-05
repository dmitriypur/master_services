<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $services = Service::query()->orderBy('name')->get();
        return ServiceResource::collection($services);
    }

    public function forMaster(User $master): AnonymousResourceCollection
    {
        $services = $master->services()->wherePivot('is_active', true)->orderBy('name')->get();
        return ServiceResource::collection($services);
    }

    public function getByParent(Request $request): JsonResponse
    {
        $parentId = $request->input('parent_id', -1);
        
        $services = Service::where('parent_id', $parentId)
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
            
        return response()->json($services);
    }
    
    // Получить полную цепочку для списка ID услуг (для инициализации)
    public function resolveChain(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
             return response()->json([]);
        }
        
        $services = Service::with('parent.parent')->whereIn('id', $ids)->get();
        
        $chains = [];
        foreach ($services as $service) {
            $subcategory = $service->parent;
            $category = $subcategory?->parent;
            
            if ($subcategory && $category) {
                $chains[] = [
                    'category_id' => $category->id,
                    'subcategory_id' => $subcategory->id,
                    'service_id' => $service->id,
                ];
            }
        }
        
        return response()->json($chains);
    }
}
