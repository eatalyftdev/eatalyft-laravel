<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(): View
    {
        return view('admin.agencies.index');
    }

    public function create(): View
    {
        return view('admin.agencies.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Agency created']);
    }

    public function show(int $id): View
    {
        return view('admin.agencies.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.agencies.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Agency updated']);
    }
}