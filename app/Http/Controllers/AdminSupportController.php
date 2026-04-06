<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSupportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = SupportTicket::with('user');

            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->category) {
                $query->where('category', $request->category);
            }
            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            $tickets = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json(['status' => 200, 'data' => $tickets]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $ticket = SupportTicket::with('user')->findOrFail($id);
            return response()->json(['status' => 200, 'data' => $ticket]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function respond(Request $request, $id)
    {
        try {
            $request->validate([
                'admin_response' => 'required|string',
                'status' => 'required|in:in_progress,resolved,closed',
            ]);

            $ticket = SupportTicket::findOrFail($id);
            $ticket->admin_response = $request->admin_response;
            $ticket->status = $request->status;
            $ticket->assigned_to = Auth::id();

            if ($request->status === 'resolved') {
                $ticket->resolved_at = now();
            }

            $ticket->save();

            return response()->json(['status' => 200, 'message' => 'Ticket response sent successfully.', 'data' => $ticket]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }
}
