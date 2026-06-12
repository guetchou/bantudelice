<?php

namespace App\Http\Controllers;

use App\Driver;
use App\DriverDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverDocumentController extends Controller
{
    private function resolveDriver(): ?Driver
    {
        $user = auth()->user();
        if (!$user) return null;

        // S3.4 — liaison directe via user_id (évite tout lookup par email/phone)
        $driver = Driver::where('user_id', $user->id)->first();
        if ($driver) return $driver;

        // Fallback pour les comptes pas encore liés : email ET téléphone doivent
        // correspondre tous les deux (orWhere permettait un IDOR par correspondance partielle).
        $driver = Driver::where('email', $user->email)
            ->where('phone', $user->phone)
            ->first();

        if ($driver && !$driver->user_id) {
            $driver->update(['user_id' => $user->id]);
        }

        return $driver;
    }

    public function index()
    {
        $driver = $this->resolveDriver();
        if (!$driver) return redirect()->route('driver.deliveries');

        $docs = DriverDocument::where('driver_id', $driver->id)
            ->get()->keyBy('type');

        return view('driver.documents', compact('driver', 'docs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(DriverDocument::$types)),
            'file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:8192',
        ]);

        $driver = $this->resolveDriver();
        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Livreur introuvable'], 403);
        }

        $existing = DriverDocument::where('driver_id', $driver->id)
            ->where('type', $request->type)
            ->first();

        // Supprimer l'ancien fichier
        if ($existing && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $path = $request->file('file')->store('driver_documents/' . $driver->id, 'public');

        $data = [
            'driver_id'     => $driver->id,
            'type'          => $request->type,
            'file_path'     => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'status'        => 'pending',
            'rejection_reason' => null,
            'reviewed_by'   => null,
            'reviewed_at'   => null,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            DriverDocument::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document soumis. En attente de vérification par notre équipe.',
        ]);
    }

    public function destroy($id)
    {
        $driver = $this->resolveDriver();
        if (!$driver) return response()->json(['success' => false], 403);

        $doc = DriverDocument::where('id', $id)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();

        return response()->json(['success' => true]);
    }
}
