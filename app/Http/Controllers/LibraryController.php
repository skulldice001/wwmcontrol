<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /**
     * Display the library index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('library.index');
    }
}
