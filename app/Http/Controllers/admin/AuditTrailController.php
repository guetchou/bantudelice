<?php

namespace App\Http\Controllers\admin;

use App\AdminAuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminAuditLog::with('admin')->orderByDesc('created_at');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('admin_email', 'like', "%{$search}%")
                  ->orWhere('path', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%");
            });
        }

        if ($method = $request->get('method')) {
            $query->where('method', strtoupper($method));
        }

        $logs = $query->paginate(50)->appends($request->only(['q', 'method']));

        return view('admin.audit_trail', compact('logs'));
    }
}
