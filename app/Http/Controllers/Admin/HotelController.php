<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function index(): View
    {
        return view('admin.hotels.index');
    }

    public function create(): View
    {
        return view('admin.hotels.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Hotel resource created']);
    }

    public function show(int $id): View
    {
        return view('admin.hotels.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.hotels.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Hotel resource updated']);
    }
}