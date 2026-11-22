<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BusController extends Controller
{
    public function index(): View
    {
        return view('admin.bus.index');
    }

    public function create(): View
    {
        return view('admin.bus.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Bus resource created']);
    }

    public function show(int $id): View
    {
        return view('admin.bus.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.bus.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Bus resource updated']);
    }
}