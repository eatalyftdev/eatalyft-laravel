<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index');
    }

    public function show(string $key): View
    {
        return view('admin.integrations.show', ['key' => $key]);
    }
}