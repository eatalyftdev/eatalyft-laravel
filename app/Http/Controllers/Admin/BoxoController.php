<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BoxoController extends Controller
{
    public function index(): View
    {
        return view('admin.boxo.index');
    }

    public function create(): View
    {
        return view('admin.boxo.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Boxo resource created']);
    }

    public function show(int $id): View
    {
        return view('admin.boxo.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.boxo.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Boxo resource updated']);
    }
}