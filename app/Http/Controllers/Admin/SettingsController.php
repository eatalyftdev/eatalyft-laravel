<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    public function index(): View
    {
        $groups = $this->settings->getEditable();
        return view('admin.settings.index', ['groups' => $groups]);
    }

    public function update(Request $request): JsonResponse
    {
        foreach ($request->all() as $key => $value) {
            if ($key === '_token' || $key === '_method') continue;
            $this->settings->set($key, $value, ['updated_by' => auth()->id()]);
        }
        return response()->json(['message' => 'Settings updated']);
    }

    public function updateSystem(Request $request): JsonResponse
    {
        return $this->update($request);
    }
}