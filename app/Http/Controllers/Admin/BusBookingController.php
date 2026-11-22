<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BusBookingController extends Controller
{
    public function index(): View
    {
        return view('admin.bus.bookings.index');
    }

    public function create(): View
    {
        return view('admin.bus.bookings.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Bus booking created']);
    }

    public function show(int $id): View
    {
        return view('admin.bus.bookings.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.bus.bookings.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Bus booking updated']);
    }
}