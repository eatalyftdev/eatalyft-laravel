<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EsimController extends Controller
{
    public function index(): View
    {
        return view('admin.esim.index');
    }

    public function create(): View
    {
        return view('admin.esim.create');
    }

    public function store(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'eSIM resource created']);
    }

    public function show(int $id): View
    {
        return view('admin.esim.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.esim.edit', ['id' => $id]);
    }

    public function update(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'eSIM resource updated']);
    }
}