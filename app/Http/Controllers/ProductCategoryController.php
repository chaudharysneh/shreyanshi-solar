<?php

namespace App\Http\Controllers;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return view('masters.product_categories.index');
    }
}
