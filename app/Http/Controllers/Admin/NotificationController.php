<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index');
    }

    public function show(int $id): View
    {
        return view('admin.notifications.show', ['id' => $id]);
    }
}