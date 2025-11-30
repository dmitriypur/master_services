<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterAnalyticsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $end = Carbon::now();
        $start = $end->copy()->subDays(30);

        $base = Appointment::query()
            ->where('master_id', $user->id)
            ->whereBetween('starts_at', [$start, $end]);

        $totalAppointments = (clone $base)->count();
        $canceledCount = (clone $base)->where('status', Appointment::STATUS_CANCELED)->count();

        $totalRevenue = Appointment::query()
            ->where('master_id', $user->id)
            ->whereBetween('starts_at', [$start, $end])
            ->where('status', Appointment::STATUS_COMPLETED)
            ->leftJoin('master_services', function ($join): void {
                $join->on('appointments.master_id', '=', 'master_services.master_id')
                    ->on('appointments.service_id', '=', 'master_services.service_id');
            })
            ->sum(DB::raw('COALESCE(appointments.price, master_services.price, 0)'));

        $cancellationRate = $totalAppointments > 0
            ? round(($canceledCount / $totalAppointments) * 100, 2)
            : 0.0;

        return response()->json([
            'total_revenue' => (int) $totalRevenue,
            'total_appointments' => (int) $totalAppointments,
            'cancellation_rate' => $cancellationRate,
        ]);
    }
}
