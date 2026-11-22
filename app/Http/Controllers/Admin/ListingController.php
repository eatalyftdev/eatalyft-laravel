<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(): View
    {
        return view('admin.listings.index');
    }

    public function create(): View
    {
        return view('admin.listings.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Listing created']);
    }

    public function show(int $id): View
    {
        return view('admin.listings.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.listings.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Listing updated']);
    }
}