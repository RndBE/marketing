<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditLogMiddleware
{
    private const AUDITED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const SENSITIVE_KEYS = [
        '_token',
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
    ];

    private static ?bool $auditTableExists = null;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldAudit($request)) {
            return $next($request);
        }

        $beforeUserId = Auth::id();
        $response = $next($request);
        $afterUserId = Auth::id();

        $this->storeAuditLog($request, $response, $beforeUserId, $afterUserId);

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        return in_array($request->method(), self::AUDITED_METHODS, true);
    }

    private function storeAuditLog(Request $request, Response $response, ?int $beforeUserId, ?int $afterUserId): void
    {
        if (!$this->auditTableExists()) {
            return;
        }

        try {
            $route = $request->route();
            $routeName = $this->resolveRouteName($request, $route?->getName());

            $payload = $this->buildPayload($request, $route?->parameters() ?? []);

            AuditLog::create([
                'user_id' => $afterUserId ?? $beforeUserId,
                'action' => $this->resolveAction($request, $routeName, $afterUserId),
                'method' => $request->method(),
                'route_name' => $routeName,
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload,
                'status_code' => $response->getStatusCode(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to store audit log.', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $routeParameters
     * @return array<string, mixed>|null
     */
    private function buildPayload(Request $request, array $routeParameters): ?array
    {
        $input = $request->all();
        unset($input['_method']);

        $payload = [
            'input' => $this->sanitizeArray($input),
            'query' => $this->sanitizeArray($request->query()),
            'route_params' => $this->sanitizeArray($routeParameters),
            'content_type' => (string) $request->header('Content-Type', ''),
        ];

        $files = $request->allFiles();
        if ($files !== []) {
            $payload['files'] = $this->sanitizeArray($files);
        }

        if (($payload['input'] ?? []) === []) {
            $raw = (string) $request->getContent();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload['raw_json'] = $this->sanitizeArray($decoded);
                } else {
                    $payload['raw_body_meta'] = [
                        'length' => strlen($raw),
                        'sha1' => sha1($raw),
                    ];
                }
            }
        }

        $payload = array_filter($payload, static function ($value) {
            if (is_array($value)) {
                return $value !== [];
            }
            return $value !== null && $value !== '';
        });

        return $payload !== [] ? $payload : null;
    }

    private function resolveAction(Request $request, ?string $routeName, ?int $afterUserId): string
    {
        if (($request->routeIs('login') || $this->isLoginRequest($request)) && $request->isMethod('post')) {
            return $afterUserId ? 'auth.login' : 'auth.login_failed';
        }

        if (($request->routeIs('logout') || $this->isLogoutRequest($request)) && $request->isMethod('post')) {
            return 'auth.logout';
        }

        if (($request->routeIs('register') || $this->isRegisterRequest($request)) && $request->isMethod('post')) {
            return 'auth.register';
        }

        return $routeName ?: strtolower($request->method()) . ' ' . trim($request->path(), '/');
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $stringKey = (string) $key;
            if ($this->isSensitiveKey($stringKey)) {
                $sanitized[$stringKey] = '[REDACTED]';
                continue;
            }

            $sanitized[$stringKey] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'filename' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime' => $value->getClientMimeType(),
            ];
        }

        if ($value instanceof EloquentModel) {
            return [
                'model' => $value::class,
                'id' => $value->getKey(),
            ];
        }

        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (method_exists($value, '__toString')) {
            return (string) $value;
        }

        return get_debug_type($value);
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function resolveRouteName(Request $request, ?string $routeName): ?string
    {
        if ($routeName) {
            return $routeName;
        }

        if ($this->isLoginRequest($request) && $request->isMethod('post')) {
            return 'login';
        }

        if ($this->isLogoutRequest($request) && $request->isMethod('post')) {
            return 'logout';
        }

        if ($this->isRegisterRequest($request) && $request->isMethod('post')) {
            return 'register';
        }

        return null;
    }

    private function isLoginRequest(Request $request): bool
    {
        return $request->is('login');
    }

    private function isLogoutRequest(Request $request): bool
    {
        return $request->is('logout');
    }

    private function isRegisterRequest(Request $request): bool
    {
        return $request->is('register');
    }

    private function auditTableExists(): bool
    {
        if (self::$auditTableExists !== null) {
            return self::$auditTableExists;
        }

        try {
            self::$auditTableExists = Schema::hasTable('audit_logs');
        } catch (Throwable) {
            self::$auditTableExists = false;
        }

        return self::$auditTableExists;
    }
}
