<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $theme = $request->input('theme');
        $request->session()->put('theme', $theme);

        return response()->json(['theme' => $theme]);
    }
}