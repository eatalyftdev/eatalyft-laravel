<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request): JsonResponse
    {
        $locale = $request->input('locale');
        $supported = ['en', 'fr'];

        if (! in_array($locale, $supported)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported language',
            ], 400);
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return response()->json([
            'success' => true,
            'locale' => $locale,
            'supported' => $supported,
        ]);
    }

    public function current(): JsonResponse
    {
        $locale = Session::get('locale', config('app.locale', 'en'));

        return response()->json([
            'locale' => $locale,
            'supported' => ['en', 'fr'],
        ]);
    }
}
