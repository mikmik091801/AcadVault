<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SearchTerm;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin'),
        ];
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'action' => (string) $request->query('action', ''),
            'user_id' => (string) $request->query('user_id', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['user_id'] !== '', fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when($filters['search'] !== '', fn ($q) => $q->where(function ($sub) use ($filters) {
                $term = $filters['search'];
                SearchTerm::where($sub, 'ip_address', $term);
                SearchTerm::orWhere($sub, 'target_type', $term);
                SearchTerm::orWhere($sub, 'action', $term);
                $sub->orWhereHas('user', fn ($u) => SearchTerm::whereAllWords($u, ['name', 'email'], $term));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $since = now()->subDay();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $this->availableActions(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'stats' => [
                ['label' => 'Total events', 'value' => AuditLog::count(), 'icon' => 'bi-clipboard-data', 'variant' => 'navy'],
                ['label' => 'Failed logins (24h)', 'value' => AuditLog::where('action', AuditLogger::LOGIN_FAILED)->where('created_at', '>=', $since)->count(), 'icon' => 'bi-exclamation-triangle', 'variant' => 'danger'],
                ['label' => 'Denied access (24h)', 'value' => AuditLog::where('action', AuditLogger::ACCESS_DENIED)->where('created_at', '>=', $since)->count(), 'icon' => 'bi-shield-exclamation', 'variant' => 'warning'],
                ['label' => 'Events (24h)', 'value' => AuditLog::where('created_at', '>=', $since)->count(), 'icon' => 'bi-activity', 'variant' => 'accent'],
            ],
        ]);
    }

    /**
     * Actions that actually appear in the table, labelled for the filter.
     *
     * @return array<string, string>
     */
    private function availableActions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->mapWithKeys(fn (string $action) => [$action => AuditLogger::describe($action)['label']])
            ->all();
    }
}
