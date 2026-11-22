<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        return view('admin.audit.index');
    }

    public function show(int $id): View
    {
        return view('admin.audit.show', ['id' => $id]);
    }
}