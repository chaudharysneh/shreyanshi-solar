<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Support\Facades\Storage;

class CategoriesController extends Controller
{
    public function index()
    {
        return view('crm.categories.index');
    }

    public function image(Categories $category)
    {
        if (!$category->image || !Storage::disk('public')->exists($category->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($category->image));
    }
}
