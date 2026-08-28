<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AuditController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:audit.view'),
        ];
    }

    public function index(Request $request)
    {
        $tab      = $request->input('tab', 'log');       // 'log' | 'changes'
        $module   = $request->input('module');
        $action   = $request->input('action');
        $userId   = $request->input('user_id');
        $from     = $request->input('from');
        $to       = $request->input('to');
        $search   = $request->input('search');

        $query = ActivityLog::with('user')
            ->when($module, fn($q) => $q->forModule($module))
            ->when($action, fn($q) => $q->forAction($action))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($from || $to, fn($q) => $q->inDateRange($from, $to))
            ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
            ->orderByDesc('created_at');

        // La pestaña Historial solo muestra registros con before/after
        if ($tab === 'changes') {
            $query->withChanges();
        }

        $logs = $query->paginate(25)->withQueryString();

        // Listas para los filtros del frontend
        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return Inertia::render('Admin/Audit/Index', [
            'logs'    => $logs,
            'modules' => $modules->map(fn($m) => ['value' => $m, 'label' => ActivityLog::moduleLabel($m)]),
            'actions' => $actions->map(fn($a) => ['value' => $a, 'label' => ActivityLog::actionLabel($a)]),
            'filters' => compact('tab', 'module', 'action', 'userId', 'from', 'to', 'search'),
        ]);
    }
}
