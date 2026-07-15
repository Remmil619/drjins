<?php

namespace App\Http\Controllers;

class BookingController extends Controller
{
    public function create()
    {
        return redirect()->route('appointments.index');
    }

    public function store()
    {
        return redirect()->route('appointments.index');
    }
}
