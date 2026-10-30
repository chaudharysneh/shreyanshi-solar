<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Http\Request;

class ProductInventoryController extends Controller
{
    public function index(Request $request)
    {
        return view('crm.inventory.index');
    }

    public function history(Product $product)
    {
        return view('crm.inventory.history', compact('product'));
    }
}
