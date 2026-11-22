<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FxController extends Controller
{
    public function index(): View
    {
        return view('admin.fx.index');
    }

    public function create(): View
    {
        return view('admin.fx.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'FX resource created']);
    }

    public function show(int $id): View
    {
        return view('admin.fx.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.fx.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'FX resource updated']);
    }
}