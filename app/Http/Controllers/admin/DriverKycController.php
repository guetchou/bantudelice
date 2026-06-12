<?php

namespace App\Http\Controllers\admin;

use App\Driver;
use App\DriverDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverKycController extends Controller
{
    public function show(Driver $driver)
    {
        $docs = DriverDocument::where('driver_id', $driver->id)
            ->get()->keyBy('type');

        $allApproved = count($docs) === count(DriverDocument::$types)
            && $docs->every(fn($d) => $d->isApproved());

        return view('admin.driver.kyc', compact('driver', 'docs', 'allApproved'));
    }

    public function approve(Driver $driver, DriverDocument $document)
    {
        abort_if($document->driver_id !== $driver->id, 403);

        $document->update([
            'status'           => 'approved',
            'rejection_reason' => null,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        Log::info('KYC document approved', [
            'admin_id'    => auth()->id(),
            'driver_id'   => $driver->id,
            'document_id' => $document->id,
            'type'        => $document->type,
        ]);

        // Activer le livreur si tous les docs sont approuvés
        $allApproved = DriverDocument::where('driver_id', $driver->id)
            ->where('status', 'approved')->count() === count(DriverDocument::$types);

        if ($allApproved && !$driver->approved) {
            $driver->update(['approved' => 1]);
            Log::info('Driver auto-approved after full KYC', ['driver_id' => $driver->id]);
        }

        return back()->with('success', "Document « {$document->typeLabel()} » approuvé.");
    }

    public function reject(Request $request, Driver $driver, DriverDocument $document)
    {
        abort_if($document->driver_id !== $driver->id, 403);

        $request->validate(['reason' => 'required|string|max:500']);

        $document->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        Log::info('KYC document rejected', [
            'admin_id'    => auth()->id(),
            'driver_id'   => $driver->id,
            'document_id' => $document->id,
            'type'        => $document->type,
            'reason'      => $request->reason,
        ]);

        return back()->with('warning', "Document « {$document->typeLabel()} » refusé.");
    }
}
