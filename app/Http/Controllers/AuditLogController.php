<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'in:POST,PUT,PATCH,DELETE'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status_code' => ['nullable', 'integer', 'between:100,599'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $q = trim((string) ($filters['q'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));
        $method = (string) ($filters['method'] ?? '');
        $userId = $filters['user_id'] ?? null;
        $statusCode = $filters['status_code'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $companyId = $this->currentCompanyId($request->user());

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('action', 'like', "%{$q}%")
                        ->orWhere('route_name', 'like', "%{$q}%")
                        ->orWhere('url', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($uq) use ($q) {
                            $uq->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->when($action !== '', fn($query) => $query->where('action', 'like', "%{$action}%"))
            ->when($method !== '', fn($query) => $query->where('method', $method))
            ->when($userId, fn($query) => $query->where('user_id', $userId))
            ->when($statusCode, fn($query) => $query->where('status_code', $statusCode))
            ->when($dateFrom, fn($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        return view('audit_logs.index', compact(
            'logs',
            'users',
            'methods',
            'q',
            'action',
            'method',
            'userId',
            'statusCode',
            'dateFrom',
            'dateTo'
        ));
    }
}
