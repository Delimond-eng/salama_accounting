<?php

namespace App\Http\Controllers;

use App\Services\DashboardComptableService;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected DashboardComptableService $dashboard
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('dashboard');
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $filtres = array_filter([
                'devise_affichage' => $request->get('devise_affichage'),
                'scope_devise' => $request->get('scope_devise'),
                'mode_conversion' => $request->get('mode_conversion'),
            ], fn ($v) => $v !== null && $v !== '');
            $payload = $this->dashboard->assembler($societeId, $filtres ?: null);

            return response()->json(['status' => 'success', 'data' => $payload]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function notifications(): JsonResponse
    {
        try {
            $societeId = SocieteContext::requireId();
            $payload = $this->dashboard->assembler($societeId);

            return response()->json([
                'status' => 'success',
                'alertes' => $payload['alertes'] ?? ['items' => [], 'count' => 0]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
