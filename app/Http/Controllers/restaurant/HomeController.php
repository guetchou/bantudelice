<?php

namespace App\Http\Controllers\restaurant;

use App\Http\Controllers\Controller;
use App\Restaurant;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function delivery_boundary()
    {
        $restaurant = Restaurant::where('user_id', auth()->id())->first();
        return view('restaurant.delivery_boundary')->with('restaurant', $restaurant);
    }
}
