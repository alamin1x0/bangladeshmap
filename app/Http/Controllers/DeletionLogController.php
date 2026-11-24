<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeletionLogController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $search = trim($request->string('q')->toString());
        $from = $request->date('from');
        $to = $request->date('to');

        $logs = DeletionLog::query()
            ->when(isset(DeletionLog::TYPES[$type]), fn ($q) => $q->where('entity_type', $type))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('entity_name', 'like', "%{$search}%")
                ->orWhere('entity_code', 'like', "%{$search}%")
                ->orWhere('parent_name', 'like', "%{$search}%")
                ->orWhere('admin_name', 'like', "%{$search}%")))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('deletion-logs.index', [
            'logs' => $logs,
            'type' => $type,
            'search' => $search,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'counts' => DeletionLog::selectRaw('entity_type, count(*) as total')->groupBy('entity_type')->pluck('total', 'entity_type'),
        ]);
    }
}
