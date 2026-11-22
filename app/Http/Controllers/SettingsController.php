<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'currency_preference' => 'required|string|in:'.implode(',', config('currencies.supported', ['XAF', 'USD', 'EUR', 'GBP'])),
            'locale' => 'required|string|in:en,fr',
        ]);

        $user->update($validated);

        if ($request->has('locale')) {
            app()->setLocale($validated['locale']);
            session(['locale' => $validated['locale']]);
        }

        if ($request->has('currency_preference')) {
            session(['currency' => $validated['currency_preference']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
