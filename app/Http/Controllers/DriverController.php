<?php

namespace App\Http\Controllers;

use App\Driver;
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
                'image' => 'nullable|image|mimes:jpeg,png,jpg',
                'account_name'=>'nullable',
                'account_number' => 'required',
                'bank_name'=>'required',
                'branch_name'=>'required',
                'branch_address'=>'required',
                'licence_image' => 'required|image|mimes:jpeg,png,jpg',

        ]);
        $request['password'] = bcrypt($request->password);
        $alert = [];
        //dd($request->all());
        $driver=Driver::create($request->all());


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
