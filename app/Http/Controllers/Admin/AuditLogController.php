<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Compliance\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Support\DataTable;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
  public function index(AuditLogger $audit)
  {
    return view('content.admin.audit', [
      'broken' => $audit->verifyChain(),
      'total' => AuditLog::query()->count(),
    ]);
  }

  public function data(Request $request)
  {
    $query = AuditLog::query()->with('user')->latest('id');

    return DataTable::make($request, $query, [0 => 'id', 1 => 'created_at', 3 => 'action'],
      fn($q, $term) => $q->where('action', 'like', "%{$term}%")->orWhere('subject_type', 'like', "%{$term}%")->orWhere('ip', 'like', "%{$term}%"),
      fn(AuditLog $log) => [
        'id' => $log->id,
        'date' => DataTable::date($log->created_at),
        'user' => $log->user ? DataTable::person($log->user->name, $log->ip) : '<span class="text-muted">Sistema</span>',
        'action' => '<span class="badge bg-label-primary">' . e($log->action) . '</span>',
        'subject' => e(trim(($log->subject_type ?? '') . ' #' . ($log->subject_id ?? ''), ' #')),
        'changes' => '<small class="text-muted text-wrap d-inline-block" style="max-width:360px">' . e(json_encode($log->after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</small>',
        'hash' => '<code class="small">' . substr($log->hash, 0, 12) . '…</code>',
      ]);
  }
}
