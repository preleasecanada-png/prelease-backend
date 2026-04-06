<?php

namespace App\Http\Controllers;

use App\Models\RentalApplication;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = RentalApplication::with(['renter', 'landlord', 'property', 'documents']);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $applications = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json(['status' => 200, 'data' => $applications]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $application = RentalApplication::with(['renter', 'landlord', 'property.propertyImages', 'documents'])->findOrFail($id);
            return response()->json(['status' => 200, 'data' => $application]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function verifyDocument(Request $request, $id)
    {
        try {
            $request->validate([
                'verification_status' => 'required|in:verified,rejected',
                'admin_notes' => 'nullable|string',
            ]);

            $document = ApplicationDocument::findOrFail($id);
            $document->verification_status = $request->verification_status;
            $document->admin_notes = $request->admin_notes;
            $document->save();

            return response()->json(['status' => 200, 'message' => 'Document verification status updated.', 'data' => $document]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }
}
