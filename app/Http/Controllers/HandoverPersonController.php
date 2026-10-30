<?php

namespace App\Http\Controllers;

class HandoverPersonController extends Controller
{
    public function index()
    {
        return view('crm.handover-persons.index');
    }
}
