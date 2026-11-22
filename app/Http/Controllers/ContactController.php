<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function show()
    {
        return view('public.contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type' => 'required|in:general,support,partnership,press,billing',
            'message' => 'required|string|max:5000',
            'cf-turnstile-response' => 'required|string',
        ]);

        $verified = $this->verifyTurnstile($validated['cf-turnstile-response']);

        if (!$verified) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => __('Security check failed. Please try again.'),
            ]);
        }

        // TODO: Send email notification
        Log::info('Contact form submission', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'type' => $validated['type'],
        ]);

        return back()->with('success', __('Thank you for your message. We\'ll get back to you soon.'));
    }

    private function verifyTurnstile(string $token): bool
    {
        $secret = config('services.turnstile.secret');

        if (!$secret) {
            // In local/dev without Turnstile config, allow through
            return app()->environment('local', 'testing');
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
            ]);

            return $response->json('success', false);
        } catch (\Exception $e) {
            Log::error('Turnstile verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}