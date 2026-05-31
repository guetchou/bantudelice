<?php

namespace App\Http\Controllers\admin;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Restaurant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $drivers = Driver::all();
        return view('admin.driver.index')->with('drivers', $drivers);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $restaurants = Restaurant::orderBy('name')->get(['id', 'name']);

        return view('admin.driver.create')->with('restaurants', $restaurants);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->boolean('provision_batch')) {
            return $this->storeProvisionedBatch($request);
        }

        $request->validate([
                'name'=>'required',
                'email' => 'required|email|max:255|unique:drivers',
                'password' => 'required',
                'phone'=>'required|unique:drivers',
                'restaurant_id' => 'nullable|exists:restaurants,id',
                'cnic' => 'required|string|max:191|unique:drivers,cnic',
                'address'=>'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
                'account_name'=>'nullable',
                'account_number' => 'required',
                'bank_name'=>'required',
                'branch_name'=>'required',
                'branch_address'=>'required',
                'licence_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',

        ]);
        $plainPassword = $request->password;
        $request['password'] = bcrypt($request->password);
        $alert = [];

        $driver = new Driver();
        $driver->restaurant_id = $request->restaurant_id;
        $driver->name = $request->name;
        $driver->user_name = $this->buildUniqueUsername(
            Str::slug(pathinfo($request->email, PATHINFO_FILENAME), '_') ?: 'driver'
        );
        $driver->email = $request->email;
        $driver->password = $request->password;
        $driver->phone = $request->phone;
        $driver->cnic = $request->cnic;
        $driver->address = $request->address;
        $driver->account_name = $request->account_name;
        $driver->account_number = $request->account_number;
        $driver->bank_name = $request->bank_name;
        $driver->branch_name = $request->branch_name;
        $driver->branch_address = $request->branch_address;
        $driver->hourly_pay = 0;
        $driver->avg_rating = 0;
        $driver->rating_count = 0;
        $driver->status = $request->input('status', 'offline');
        $driver->approved = $request->boolean('approved', true);
        $driver->password_must_change = true;
        $driver->password_changed_at = null;
        $driver->password_temp_issued_at = now();
        $driver->provisioned_by_admin_id = auth()->id();
        $driver->save();


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
            $filename = str_replace(" ", "-", $filename);
            $image->move($destination, $filename);
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
            $file = str_replace(" ", "-", $file);
            $licence_image->move($destination, $file);
            $driver->licence_image = $file;
            $driver->save();
        }
        $alert['type'] = 'success';
        $alert['message'] = 'Livreur créé avec succès';
        return redirect()
            ->route('driver.index')
            ->with('alert', $alert)
            ->with('provisioned_accounts', [[
                'id' => $driver->id,
                'name' => $driver->name,
                'restaurant' => optional($driver->restaurant)->name ?? 'Pool mutualise',
                'user_name' => $driver->user_name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'password' => $plainPassword,
                'status' => $driver->status,
            ]]);
    }

    protected function storeProvisionedBatch(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'quantity' => 'required|integer|min:1|max:20',
            'name_prefix' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:191',
            'status' => 'required|in:online,offline',
            'approved' => 'nullable|boolean',
        ]);

        $restaurant = null;
        if (!empty($validated['restaurant_id'])) {
            $restaurant = Restaurant::findOrFail($validated['restaurant_id']);
        }

        $namePrefix = trim((string) ($validated['name_prefix'] ?? ''));
        if ($namePrefix === '') {
            $namePrefix = $restaurant ? 'Livreur Ops ' . $restaurant->name : 'Livreur Ops Mutualise';
        }

        $address = trim((string) ($validated['address'] ?? ''));
        if ($address === '') {
            $address = $restaurant->address ?? 'Brazzaville';
        }

        $status = $validated['status'];
        $approved = $request->boolean('approved', true);
        $quantity = (int) $validated['quantity'];
        $slugBase = $restaurant
            ? 'ops_r' . $restaurant->id
            : 'ops_pool';

        $provisionedAccounts = DB::transaction(function () use ($quantity, $restaurant, $namePrefix, $address, $status, $approved, $slugBase) {
            $accounts = [];

            for ($i = 0; $i < $quantity; $i++) {
                $suffix = $this->nextProvisionSuffix($slugBase);
                $username = $slugBase . '_' . $suffix;
                $password = Str::random(16);

                $driver = new Driver();
                $driver->restaurant_id = $restaurant?->id;
                $driver->name = trim($namePrefix . ' ' . $suffix);
                $driver->user_name = $username;
                $driver->email = $username . '@bantudelice.cg';
                $driver->password = bcrypt($password);
                $driver->phone = $this->buildProvisionPhone($restaurant?->id, $suffix);
                $driver->cnic = strtoupper(str_replace('_', '-', $username));
                $driver->address = $address;
                $driver->hourly_pay = 0;
                $driver->avg_rating = 0;
                $driver->rating_count = 0;
                $driver->status = $status;
                $driver->approved = $approved;
                $driver->password_must_change = true;
                $driver->password_changed_at = null;
                $driver->password_temp_issued_at = now();
                $driver->provisioned_by_admin_id = auth()->id();
                $driver->save();

                $accounts[] = [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'restaurant' => $restaurant?->name ?? 'Pool mutualise',
                    'user_name' => $driver->user_name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'password' => $password,
                    'status' => $driver->status,
                ];
            }

            return $accounts;
        });

        $alert = [
            'type' => 'success',
            'message' => $quantity . ' livreur(s) provisionné(s) avec succès',
        ];

        return redirect()
            ->route('driver.index')
            ->with('alert', $alert)
            ->with('provisioned_accounts', $provisionedAccounts);
    }

    protected function nextProvisionSuffix(string $slugBase): string
    {
        $index = 1;

        while (true) {
            $suffix = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $username = $slugBase . '_' . $suffix;

            if (!Driver::where('user_name', $username)->exists()) {
                return $suffix;
            }

            $index++;
        }
    }

    protected function buildUniqueUsername(string $base): string
    {
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'driver';
        }

        $candidate = $base;
        $index = 1;
        while (Driver::where('user_name', $candidate)->exists()) {
            $candidate = $base . '_' . $index;
            $index++;
        }

        return $candidate;
    }

    protected function buildProvisionPhone(?int $restaurantId, string $suffix): string
    {
        $restaurantPart = str_pad((string) ($restaurantId ?? 0), 2, '0', STR_PAD_LEFT);
        $sequencePart = str_pad((string) ((int) $suffix), 3, '0', STR_PAD_LEFT);

        return '+2420679' . $restaurantPart . $sequencePart;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Driver $driver)
    {
        return view('admin.driver.edit')->with('driver', $driver);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Driver $driver)
    {
        $request->validate([
        'name'=>'required',
                
                'address'=>'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
                'account_name'=>'nullable',
                'account_number' => 'required',
                'bank_name'=>'required',
                'branch_name'=>'required',
                'branch_address'=>'required',
                'licence_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',

        ]);
        if($request->password !='')
        {
           $request['password'] = bcrypt($request->password); 
        }
        else{
            $request['password'] = $driver->password;
        }
        
        $alert = [];
        if ($request->email)
             if ($request->email != $driver->email) {
                 $request->validate([
                     'email' => 'required|email|max:255|unique:drivers',
                 ]);
             } else
                 $request->request->remove('email');
         if ($request->phone)
             if ($request->phone != $driver->phone) {
                 $request->validate([
                     'phone' => 'required|string|max:191|unique:drivers',
                 ]);
             } else
                 $request->request->remove('phone');
        
        $driver->update($request->all());
        
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
            $filename = str_replace(" ", "-", $filename);
            $image->move($destination, $filename);
            $driver->image = $filename;
            $driver->save();
        }

        $licence_image = $request->licence_image;
        $destination = 'images/driver_images';
        if ($request->hasFile('licence_image')) {
            $filename = strtolower(
                pathinfo($licence_image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-'
                . uniqid()
                . '.'
                . $licence_image->getClientOriginalExtension()
            );
            $filename = str_replace(" ", "-", $filename);
            $licence_image->move($destination, $filename);
            $driver->licence_image = $filename;
            $driver->save();
        }
        
        $alert['type'] = 'success';
        $alert['message'] = 'Livreur mis à jour avec succès';
        return redirect()->route('driver.index')->with('alert', $alert);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Driver $driver)
    {
        if ($driver->image)
            Storage::delete('images/driver_images/' . $driver->image);
        $driver->delete();
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Livreur supprimé avec succès';

        return redirect()->route('driver.index')->with('alert', $alert);
    }
    public function change_driver_active_status(Driver $driver)
    {
        if ($driver->approved)
            $driver->approved = false;
        else
            $driver->approved = true;
        $driver->save();
        $alert = [];
        $alert['type'] = 'success';
        $alert['message'] = 'Statut du livreur mis à jour avec succès';
        return redirect()->back()->with('alert', $alert);
    }
    public function get_hourly_pay(Driver $driver)
    {
        return view('admin.driver.set_hourly_pay')->with('driver', $driver);

    }
    public function set_hourly_pay(Driver $driver , Request $request)
    {
        $request->validate([
            'hourly_pay'=>'required',
        ]);
        $pay=$request->hourly_pay;
        Driver::where('id',$driver->id)->update(array('hourly_pay'=>$pay));
        $alert['type'] = 'success';
        $alert['message'] = 'Livreur mis à jour avec succès';
        return redirect()->route('driver.index')->with('alert', $alert);
    }
}
