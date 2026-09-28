<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * GET /healthz: liveness plus database connectivity (shared preview interface).
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'env' => config('app.env'),
            'users' => User::query()->count(),
        ]);
    }
}
