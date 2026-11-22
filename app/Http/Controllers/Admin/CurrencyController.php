<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(): View
    {
        return view('admin.currencies.index');
    }

    public function create(): View
    {
        return view('admin.currencies.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Currency created']);
    }

    public function show(int $id): View
    {
        return view('admin.currencies.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.currencies.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Currency updated']);
    }
}