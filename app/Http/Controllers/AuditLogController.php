<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
        $query = AuditLog::with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }
        if ($entity = $request->get('entity_type')) {
            $query->where('entity_type', $entity);
        }
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->limit(200)->get()->map(fn ($l) => [
            'id' => $l->id,
            'action' => $l->action,
            'entity_type' => $l->entity_type,
            'entity_id' => $l->entity_id,
            'reference' => $l->reference,
            'description' => $l->description,
            'user_name' => $l->user?->name ?? '—',
            'user_email' => $l->user?->email,
            'created_at' => $l->created_at?->format('d/m/Y H:i'),
        ]);

        return response()->json(['status' => 'success', 'logs' => $logs]);
    }
}
