<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Mcp\Servers\PenawaranServer;
use Laravel\Mcp\Facades\Mcp;

// Token dibuat lewat `php artisan mcp:token <email>`. Audit dipasang sendiri karena
// route ini di luar grup `web`; throttle sesudah auth supaya dihitung per pengguna.
Mcp::web('/mcp/penawaran', PenawaranServer::class)
    ->middleware([AuditLogMiddleware::class, 'auth:sanctum', 'throttle:60,1', 'permission:create-penawaran'])
    ->name('mcp.penawaran');
