<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(): JsonResponse
    {
        return response()->json(['message' => 'User created']);
    }

    public function show(int $id): View
    {
        return view('admin.users.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.users.edit', ['id' => $id]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json(['message' => 'User updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['message' => 'User deleted']);
    }
}