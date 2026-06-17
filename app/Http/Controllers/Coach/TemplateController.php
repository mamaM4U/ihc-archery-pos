<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\StoreTemplateRequest;
use App\Http\Requests\Coach\UpdateTemplateRequest;
use App\Models\CoachWeeklyTemplate;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $query = CoachWeeklyTemplate::query()
            ->with(['coach:id,name', 'templateSlots'])
            ->withCount('templateSlots');

        if (! $user->hasRole('admin')) {
            $query->where('coach_id', $user->id);
        }

        $templates = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Coach/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $coaches = collect();

        if ($user->hasRole('admin')) {
            // Retrieve all users who are coaches
            $coaches = User::role('coach')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Coach/Templates/Create', [
            'coaches' => $coaches,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $coachId = $user->hasRole('admin') ? (int) $request->input('coach_id') : $user->id;

        $template = DB::transaction(function () use ($request, $coachId) {
            $isActive = $request->boolean('is_active', true);

            // If template is active, deactivate all other templates of this coach
            if ($isActive) {
                CoachWeeklyTemplate::where('coach_id', $coachId)
                    ->update(['is_active' => false]);
            }

            $template = CoachWeeklyTemplate::create([
                'coach_id' => $coachId,
                'template_name' => $request->input('template_name'),
                'booking_open_days' => $request->input('booking_open_days', 7),
                'is_active' => $isActive,
                'notes' => $request->input('notes'),
            ]);

            foreach ($request->input('slots', []) as $slotData) {
                $template->templateSlots()->create([
                    'day_of_week' => $slotData['day_of_week'],
                    'session_name' => $slotData['session_name'],
                    'start_time' => $slotData['start_time'],
                    'end_time' => $slotData['end_time'],
                    'location' => $slotData['location'],
                    'max_capacity' => $slotData['max_capacity'],
                    'duration_minutes' => $slotData['duration_minutes'],
                ]);
            }

            return $template;
        });

        $coachName = User::find($coachId)->name;

        $this->auditLogService->log(
            event: 'template.created',
            module: 'templates',
            auditable: $template,
            description: "Template mingguan baru dibuat untuk coach {$coachName}.",
            after: [
                'template_name' => $template->template_name,
                'booking_open_days' => $template->booking_open_days,
                'is_active' => $template->is_active,
                'slots_count' => count($request->input('slots', [])),
            ]
        );

        return to_route('templates.index')->with('success', 'Template mingguan berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CoachWeeklyTemplate $template): Response
    {
        $user = Auth::user();

        // Authorize: Admin or Coach who owns the template
        if (! $user->hasRole('admin') && $template->coach_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke template ini.');
        }

        $coaches = collect();
        if ($user->hasRole('admin')) {
            $coaches = User::role('coach')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        $template->load(['templateSlots', 'coach:id,name']);

        // Formats time strings to H:i format to avoid browser inputs complaining
        $template->templateSlots->each(function ($slot) {
            $slot->start_time = substr($slot->start_time, 0, 5);
            $slot->end_time = substr($slot->end_time, 0, 5);
        });

        return Inertia::render('Coach/Templates/Edit', [
            'template' => $template,
            'coaches' => $coaches,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTemplateRequest $request, CoachWeeklyTemplate $template): RedirectResponse
    {
        $user = Auth::user();
        $coachId = $user->hasRole('admin') ? (int) $request->input('coach_id') : $template->coach_id;

        $beforeState = [
            'template_name' => $template->template_name,
            'booking_open_days' => $template->booking_open_days,
            'is_active' => $template->is_active,
            'slots_count' => $template->templateSlots()->count(),
        ];

        DB::transaction(function () use ($request, $template, $coachId) {
            $isActive = $request->boolean('is_active', true);

            // If templates is set to active, deactivate all other templates of this coach
            if ($isActive && ! $template->is_active) {
                CoachWeeklyTemplate::where('coach_id', $coachId)
                    ->update(['is_active' => false]);
            }

            $template->update([
                'coach_id' => $coachId,
                'template_name' => $request->input('template_name'),
                'booking_open_days' => $request->input('booking_open_days'),
                'is_active' => $isActive,
                'notes' => $request->input('notes'),
            ]);

            // Recreate all slots
            $template->templateSlots()->delete();

            foreach ($request->input('slots', []) as $slotData) {
                $template->templateSlots()->create([
                    'day_of_week' => $slotData['day_of_week'],
                    'session_name' => $slotData['session_name'],
                    'start_time' => $slotData['start_time'],
                    'end_time' => $slotData['end_time'],
                    'location' => $slotData['location'],
                    'max_capacity' => $slotData['max_capacity'],
                    'duration_minutes' => $slotData['duration_minutes'],
                ]);
            }
        });

        $coachName = User::find($coachId)->name;

        $this->auditLogService->log(
            event: 'template.updated',
            module: 'templates',
            auditable: $template,
            description: "Template mingguan diperbarui untuk coach {$coachName}.",
            before: $beforeState,
            after: [
                'template_name' => $template->template_name,
                'booking_open_days' => $template->booking_open_days,
                'is_active' => $template->is_active,
                'slots_count' => count($request->input('slots', [])),
            ]
        );

        return to_route('templates.index')->with('success', 'Template mingguan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CoachWeeklyTemplate $template): RedirectResponse
    {
        $user = Auth::user();

        // Authorize: Admin or Coach who owns the template
        if (! $user->hasRole('admin') && $template->coach_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke template ini.');
        }

        $beforeState = [
            'template_name' => $template->template_name,
            'booking_open_days' => $template->booking_open_days,
            'is_active' => $template->is_active,
        ];

        DB::transaction(function () use ($template) {
            // Delete template slots first
            $template->templateSlots()->delete();
            $template->delete();
        });

        $this->auditLogService->log(
            event: 'template.deleted',
            module: 'templates',
            auditable: $template,
            description: "Template mingguan {$template->template_name} dihapus.",
            before: $beforeState
        );

        return to_route('templates.index')->with('success', 'Template mingguan berhasil dihapus.');
    }
}
