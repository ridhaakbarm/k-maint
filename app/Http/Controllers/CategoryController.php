<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('errors.404');
    }

    public function create(): View
    {
        return view('errors.404');
    }

    public function store(Request $request): RedirectResponse
    {
        return back();
    }

    public function show(string $id): View
    {
        return view('errors.404');
    }

    public function edit(string $id): View
    {
        return view('errors.404');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return back();
    }

    public function destroy(string $id): RedirectResponse
    {
        return back();
    }
}
