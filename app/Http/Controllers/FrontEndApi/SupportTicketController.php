<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();
            $tickets = SupportTicket::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json(['status' => 200, 'data' => $tickets]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'category' => 'required|in:account,payment,property,application,lease,technical,other',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();

            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'message' => $request->message,
                'category' => $request->category,
                'priority' => $request->priority ?? 'medium',
                'status' => 'open',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Support ticket created successfully!',
                'data' => $ticket
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $ticket = SupportTicket::where('user_id', $user->id)->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $ticket]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
