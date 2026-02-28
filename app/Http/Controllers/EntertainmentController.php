<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EntertainmentController extends Controller
{
    public function index()
    {
        return view('entertainment.index');
    }

    public function poker()
    {
        return view('entertainment.poker');
    }

    public function blackjack()
    {
        return view('entertainment.blackjack');
    }
}
