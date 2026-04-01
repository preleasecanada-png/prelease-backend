<?php

namespace App\Http\Controllers;

use App\Models\RentalApplication;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = RentalApplication::with(['renter', 'landlord', 'property', 'documents']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    public function show($id)
    {
        $application = RentalApplication::with(['renter', 'landlord', 'property.propertyImages', 'documents'])->findOrFail($id);
        return view('admin.applications.show', compact('application'));
    }

    public function verifyDocument(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required|in:verified,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $document = ApplicationDocument::findOrFail($id);
        $document->verification_status = $request->verification_status;
        $document->admin_notes = $request->admin_notes;
        $document->save();

        return redirect()->back()->with('success', 'Document verification status updated.');
    }
}
