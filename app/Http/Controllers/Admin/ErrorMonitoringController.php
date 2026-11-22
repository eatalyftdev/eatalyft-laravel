<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorIncident;
use Illuminate\Http\Request;

class ErrorMonitoringController extends Controller
{
    /**
     * Display the error monitoring dashboard.
     */
    public function index(Request $request)
    {
        $query = ErrorIncident::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by environment
        if ($request->filled('environment')) {
            $query->where('environment', $request->environment);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('exception_class', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('fingerprint', 'like', "%{$search}%");
            });
        }

        $incidents = $query->orderBy('last_seen_at', 'desc')->paginate(25)->withQueryString();

        $stats = [
            'total' => ErrorIncident::count(),
            'open' => ErrorIncident::where('status', 'open')->count(),
            'critical' => ErrorIncident::where('severity', 'critical')->where('status', '!=', 'resolved')->count(),
            'resolved' => ErrorIncident::where('status', 'resolved')->count(),
        ];

        $severities = ['debug', 'info', 'warning', 'error', 'critical'];
        $statuses = ['open', 'investigating', 'resolved', 'ignored'];
        $environments = ErrorIncident::distinct()->pluck('environment');

        return view('admin.monitoring.errors.index', compact(
            'incidents', 'stats', 'severities', 'statuses', 'environments'
        ));
    }

    /**
     * Display the specified incident.
     */
    public function show(ErrorIncident $incident)
    {
        return view('admin.monitoring.errors.show', compact('incident'));
    }

    /**
     * Mark the incident as investigating.
     */
    public function investigating(ErrorIncident $incident)
    {
        $incident->markInvestigating();

        return redirect()->route('admin.monitoring.errors.show', $incident)
            ->with('status', 'Incident marked as investigating.');
    }

    /**
     * Mark the incident as resolved.
     */
    public function resolve(ErrorIncident $incident)
    {
        $incident->markResolved();

        return redirect()->route('admin.monitoring.errors.show', $incident)
            ->with('status', 'Incident marked as resolved.');
    }

    /**
     * Reopen a resolved incident.
     */
    public function reopen(ErrorIncident $incident)
    {
        $incident->reopen();

        return redirect()->route('admin.monitoring.errors.show', $incident)
            ->with('status', 'Incident reopened.');
    }

    /**
     * Delete the incident.
     */
    public function destroy(ErrorIncident $incident)
    {
        $incident->delete();

        return redirect()->route('admin.monitoring.errors.index')
            ->with('status', 'Incident deleted.');
    }
}
