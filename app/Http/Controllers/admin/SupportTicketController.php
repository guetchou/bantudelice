<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::query()
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('module'), fn ($q, $module) => $q->where('module', $module))
            ->when($request->get('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(24)
            ->appends($request->all());

        $summary = [
            'open' => SupportTicket::open()->count(),
            'resolved' => SupportTicket::whereIn('status', ['resolved', 'closed'])->count(),
            'high_priority' => SupportTicket::where('priority', 'high')->count(),
        ];

        return view('admin.support_tickets.index', compact('tickets', 'summary'));
    }

    public function resolve(Request $request, SupportTicket $ticket, SupportTicketService $tickets)
    {
        $request->validate([
            'resolution' => 'required|in:resolved,closed,pending_review,pending_refund,pending_redelivery',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $tickets->resolve($ticket, $request->input('resolution'), [
            'resolution_notes' => $request->input('resolution_notes'),
            'resolved_by_id' => auth()->id(),
        ]);

        return back()->with('success', 'Ticket support mis à jour.');
    }
}
