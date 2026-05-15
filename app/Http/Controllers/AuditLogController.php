<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemErrorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $activity = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->string('action')->toString(), fn ($query, string $action) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Siay/Audit/Index', [
            'activity' => $activity,
            'errors' => SystemErrorLog::query()->with('user:id,name,email')->latest('created_at')->limit(10)->get(),
            'filters' => $request->only('action'),
        ]);
    }
}
