<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Mask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['action', 'merchant', 'user', 'date_from', 'date_to']);

        $logs = AuditLog::query()
            ->with(['merchant', 'user'])
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->whereHas('merchant', fn ($inner) => $inner
                ->where('public_id', $merchant)
                ->orWhere('business_name', 'like', "%{$merchant}%")))
            ->when($filters['user'] ?? null, fn ($query, $user) => $query->whereHas('user', fn ($inner) => $inner
                ->where('name', 'like', "%{$user}%")
                ->orWhere('email', 'like', "%{$user}%")))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'merchant' => $log->merchant ? [
                    'public_id' => $log->merchant->public_id,
                    'business_name' => $log->merchant->business_name,
                ] : null,
                'user' => $log->user ? [
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'ip_address' => $log->ip_address,
                'metadata' => $this->safeMetadata($log->metadata ?? []),
                'created_at' => $log->created_at,
            ]),
            'filters' => $filters,
        ]);
    }

    protected function safeMetadata(array $metadata): array
    {
        return Mask::arraySensitive($metadata);
    }
}
