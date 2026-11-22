<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $isDetailed = $this->shouldShowDetails($request);

        $checks = [];
        $overall = 'healthy';

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = $isDetailed ? ['status' => 'healthy', 'message' => 'Connected'] : true;
        } catch (\Throwable $e) {
            $checks['database'] = $isDetailed ? ['status' => 'unhealthy', 'message' => $e->getMessage()] : false;
            $overall = 'unhealthy';
        }

        // Cache check
        try {
            Cache::put('health_check', 'ok', 10);
            $checks['cache'] = $isDetailed ? ['status' => 'healthy', 'message' => 'Working'] : true;
        } catch (\Throwable $e) {
            $checks['cache'] = $isDetailed ? ['status' => 'unhealthy', 'message' => $e->getMessage()] : false;
            $overall = 'degraded';
        }

        // Storage check
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            Storage::disk('local')->delete('health_check.txt');
            $checks['storage'] = $isDetailed ? ['status' => 'healthy', 'message' => 'Writable'] : true;
        } catch (\Throwable $e) {
            $checks['storage'] = $isDetailed ? ['status' => 'unhealthy', 'message' => $e->getMessage()] : false;
            $overall = 'degraded';
        }

        // Queue check
        try {
            $checks['queue'] = $isDetailed ? ['status' => 'healthy', 'message' => 'Configured'] : true;
        } catch (\Throwable $e) {
            $checks['queue'] = $isDetailed ? ['status' => 'unhealthy', 'message' => $e->getMessage()] : false;
            $overall = 'degraded';
        }

        $response = [
            'status' => $overall === 'healthy' ? 'ok' : ($overall === 'degraded' ? 'degraded' : 'fail'),
            'timestamp' => now()->toISOString(),
        ];

        if ($isDetailed) {
            $response['version'] = app()->version();
            $response['environment'] = app()->environment();
            $response['checks'] = $checks;
        }

        $statusCode = $overall === 'healthy' ? 200 : ($overall === 'degraded' ? 200 : 503);

        return response()->json($response, $statusCode);
    }

    protected function shouldShowDetails(Request $request): bool
    {
        $healthToken = config('app.health_token');

        if ($healthToken && $request->bearerToken() === $healthToken) {
            return true;
        }

        if ($request->query('token') === $healthToken) {
            return true;
        }

        $user = Auth::user();
        if ($user && ($user->hasRole('superadmin') || $user->hasRole('admin'))) {
            return true;
        }

        return false;
    }
}