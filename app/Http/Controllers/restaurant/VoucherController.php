<?php

namespace App\Http\Controllers\restaurant;

use App\Voucher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('restaurant.dashboard')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        $vouchers = Voucher::where('restaurant_id', $restaurant->id)
            ->orderBy('end_date', 'desc')
            ->get();

        return view('restaurant.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('restaurant.vouchers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
        	'name' => ['required', 'string', 'max:191'],
        	'discount' => ['required', 'integer', 'min:1', 'max:100'],
        	'start_date' => ['required', 'date'],
        	'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('voucher.index')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        Voucher::create([
            'restaurant_id' => $restaurant->id,
            'name' => $request->name,
            'discount' => (int)$request->discount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $alert['type'] = 'success';
        $alert['message'] = 'Bon de réduction créé avec succès';
        return redirect()->route('voucher.index')->with('alert', $alert);
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
     * @return \Illuminate\Http\Response
     */
    public function edit(Voucher $voucher)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant || (int)$voucher->restaurant_id !== (int)$restaurant->id) {
            abort(403, 'Accès non autorisé');
        }
        return view('restaurant.vouchers.edit',compact('voucher'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
        	'name' => ['required', 'string', 'max:191'],
        	'discount' => ['required', 'integer', 'min:1', 'max:100'],
        	'start_date' => ['required', 'date'],
        	'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('voucher.index')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        $voucher = Voucher::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $voucher->update([
            'name' => $request->name,
            'discount' => (int)$request->discount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $alert['type'] = 'success';
        $alert['message'] = 'Bon de réduction mis à jour avec succès';
        return redirect()->route('voucher.index')->with('alert', $alert);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $restaurant = auth()->user()->restaurant;
        if (!$restaurant) {
            return redirect()->route('voucher.index')->with('alert', [
                'type' => 'danger',
                'message' => "Aucun restaurant n'est associé à votre compte."
            ]);
        }

        Voucher::where('id', $id)
            ->where('restaurant_id', $restaurant->id)
            ->delete();

        $alert['type'] = 'success';
        $alert['message'] = 'Bon de réduction supprimé avec succès';
        return redirect()->route('voucher.index')->with('alert', $alert);
    }
}
