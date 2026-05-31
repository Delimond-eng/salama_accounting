<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\SocieteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        return view('logs');
    }

    public function list(Request $request): JsonResponse
    {
        $societeId = SocieteContext::id();

        $query = AuditLog::with('user:id,name,email')
            ->when($societeId, fn($q) => $q->where('societe_id', $societeId))
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->limit(500)->get()->map(fn ($l) => [
            'id' => $l->id,
            'action' => $l->action,
            'reference' => $l->reference,
            'description' => $l->description,
            'user_name' => $l->user?->name ?? 'Système',
            'user_email' => $l->user?->email,
            'created_at' => $l->created_at?->format('d/m/Y H:i'),
            'created_at_iso' => $l->created_at?->toIso8601String(), // Pour le tri DataTable
        ]);

        return response()->json(['status' => 'success', 'logs' => $logs]);
    }
}
