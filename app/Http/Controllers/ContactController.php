<?php

namespace App\Http\Controllers;

use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ContactController extends Controller
{
    public function ContactUs(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'subject' => 'nullable|string|max:120',
            'message' => 'required'
        ]);

        Contact::create($request->only(['name', 'email', 'phone', 'subject', 'message']));

        return redirect()->back()->with('success', 'Votre message a bien ete envoye.');
    }
}
