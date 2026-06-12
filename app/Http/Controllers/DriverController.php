<?php

namespace App\Http\Controllers;

use App\Driver;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DriverController extends Controller
{
    
    public function driver()
    {
        return view('frontend.driver');
    }

    public function driverRegistration(Request $request)
    {
       $request->validate([
                'name'=>'required',
                'email' => 'required|email|max:255|unique:drivers',
                'password' => 'required',
                'phone'=>'required|unique:drivers',
                'address'=>'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
                'paypal_account_no' => 'required|string|max:30',
                'account_name'=>'nullable',
                'account_number' => 'nullable',
                'bank_name'=>'nullable',
                'branch_name'=>'nullable',
                'branch_address'=>'nullable',
                'licence_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',

        ]);
        $plainPassword = $request->password;
        $request['password'] = bcrypt($plainPassword);
        $alert = [];

        DB::beginTransaction();
        try {
            $driver = Driver::create($request->all());

            // Créer le compte users pour permettre la connexion web
            if (!User::where('email', $driver->email)->exists()) {
                User::create([
                    'name'     => $driver->name,
                    'email'    => $driver->email,
                    'phone'    => $driver->phone,
                    'password' => $driver->password,
                    'type'     => 'driver',
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['email' => 'Erreur lors de la création du compte. Veuillez réessayer.']);
        }


        $licence_image = $request->licence_image;
        $image = $request->image;
        $destination = 'images/driver_images';
        if ($request->hasFile('image')) {
            $filename = strtolower(
                pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-'
                . uniqid()
                . '.'
                . $image->getClientOriginalExtension()
            );
            $image->move($destination, $filename);
            str_replace(" ", "-", $filename);
            $driver->image = $filename;
            $driver->save();
        }
        
        $destination = 'images/driver_images';
        if ($request->hasFile('licence_image')) {
            $file = strtolower(
                pathinfo($licence_image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-'
                . uniqid()
                . '.'
                . $licence_image->getClientOriginalExtension()
            );
            $licence_image->move($destination, $file);
            str_replace(" ", "-", $file);
            $driver->licence_image = $file;
            $driver->save();
        }
        $data = array(
            			'name' => $driver->name,
            			'email' => $driver->email,
            		);
                   //sending email
                   Mail::to($request->email)->send(new RegisterEmail($data));
        $alert['type'] = 'success';
        $alert['message'] = 'Compte créé avec succès !';
        return redirect()->back()->with('alert', $alert); 
    }
    
}
