<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KycController extends Controller
{
    public function index(): View
    {
        return view('admin.kyc.index');
    }

    public function show(int $id): View
    {
        return view('admin.kyc.show', ['id' => $id]);
    }

    public function edit(int $id): View
    {
        return view('admin.kyc.edit', ['id' => $id]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json(['message' => 'KYC updated']);
    }
}