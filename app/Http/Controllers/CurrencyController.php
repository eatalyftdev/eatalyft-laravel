<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CurrencyController extends Controller
{
    public function switch(Request $request): JsonResponse
    {
        $currency = $request->input('currency');
        $supported = config('currencies.supported', ['XAF', 'USD', 'EUR', 'GBP']);

        if (! in_array($currency, $supported)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported currency',
            ], 400);
        }

        Session::put('currency', $currency);

        if ($request->user()) {
            $request->user()->update(['currency_preference' => $currency]);
        }

        return response()->json([
            'success' => true,
            'currency' => $currency,
            'symbol' => config("currencies.symbols.{$currency}", $currency),
            'decimal_places' => config("currencies.decimal_places.{$currency}", 2),
        ]);
    }

    public function current(): JsonResponse
    {
        $currency = Session::get('currency', config('currencies.default', 'XAF'));

        return response()->json([
            'currency' => $currency,
            'symbol' => config("currencies.symbols.{$currency}", $currency),
            'decimal_places' => config("currencies.decimal_places.{$currency}", 2),
            'supported' => config('currencies.supported', ['XAF', 'USD', 'EUR', 'GBP']),
        ]);
    }
}
