<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index');
    }

    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function store(): JsonResponse
    {
        return response()->json(['message' => 'Role created']);
    }

    public function show(int $id): View
    {
        return view('admin.roles.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.roles.edit', ['id' => $id]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json(['message' => 'Role updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['message' => 'Role deleted']);
    }
}