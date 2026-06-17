<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\CoachMember;
use App\Models\User;
use App\Models\UserRelationship;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                if ($request->status !== null && $request->status !== '') {
                    $query->where('is_active', $request->status === 'active');
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Dashboard/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $avatarPath = null;

        if ($request->file('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->is_active,
            'avatar' => $avatarPath,
        ]);

        // assign role using spatie roles trait
        $user->assignRole($request->role);

        $this->auditLogService->log(
            event: 'user.created',
            module: 'users',
            auditable: $user,
            description: "Pengguna baru dengan role {$request->role} telah dibuat.",
            after: $this->userPayload($user, [$request->role], $avatarPath !== null),
        );

        return to_route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $user->load(['roles' => fn ($query) => $query->select('id', 'name')]);

        return Inertia::render('Dashboard/Users/Edit', [
            'roles' => $roles,
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $beforeRoles = $user->roles()->pluck('name')->all();
        $before = $this->userPayload($user, $beforeRoles, false);
        $avatarPath = $user->getRawOriginal('avatar');
        $avatarChanged = false;

        if ($request->file('avatar')) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $avatarChanged = true;
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->is_active,
            'avatar' => $avatarPath,
        ]);

        if ($request->password) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        // Sync spatie roles
        $user->syncRoles([$request->role]);

        $after = $this->userPayload($user->fresh(), [$request->role], $avatarChanged);

        $this->auditLogService->log(
            event: 'user.updated',
            module: 'users',
            auditable: $user,
            description: 'Data pengguna diperbarui.',
            before: $before,
            after: $after,
        );

        if (! in_array($request->role, $beforeRoles)) {
            $this->auditLogService->log(
                event: 'user.role_changed',
                module: 'users',
                auditable: $user,
                description: 'Role pengguna diperbarui.',
                before: ['roles' => array_values($beforeRoles)],
                after: ['roles' => [$request->role]],
            );
        }

        return to_route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ids = explode(',', $id);
        $users = User::query()->with('roles')->whereIn('id', $ids)->get();

        foreach ($users as $user) {
            if ($user->avatar) {
                $avatarPath = $user->getRawOriginal('avatar');
                if ($avatarPath) {
                    Storage::disk('public')->delete($avatarPath);
                }
            }

            $this->auditLogService->log(
                event: 'user.deleted',
                module: 'users',
                auditable: $user,
                description: 'Pengguna dihapus.',
                before: $this->userPayload($user, $user->roles->pluck('name')->all(), false),
            );

            // Clean up relationships
            CoachMember::where('coach_id', $user->id)->orWhere('member_id', $user->id)->delete();
            UserRelationship::where('guardian_id', $user->id)->orWhere('member_id', $user->id)->delete();
            $user->delete();
        }

        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Display the assignments page for Coach-Member and Guardian-Member links.
     */
    public function assignments()
    {
        $coaches = User::role('coach')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $members = User::role('member')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $guardians = User::role('guardian')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        $coachMembers = CoachMember::with([
            'coach' => fn ($q) => $q->select('id', 'name', 'email'),
            'member' => fn ($q) => $q->select('id', 'name', 'email'),
        ])->latest()->get();

        $userRelationships = UserRelationship::with([
            'guardian' => fn ($q) => $q->select('id', 'name', 'email'),
            'member' => fn ($q) => $q->select('id', 'name', 'email'),
        ])->latest()->get();

        return Inertia::render('Dashboard/Users/Assignments', [
            'coaches' => $coaches,
            'members' => $members,
            'guardians' => $guardians,
            'coachMembers' => $coachMembers,
            'userRelationships' => $userRelationships,
        ]);
    }

    /**
     * Assign a member to a coach.
     */
    public function assignCoach(Request $request)
    {
        $request->validate([
            'coach_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (! $user || $user->role !== 'coach') {
                    $fail('User yang dipilih harus memiliki role Coach.');
                }
            }],
            'member_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (! $user || $user->role !== 'member') {
                    $fail('User yang dipilih harus memiliki role Member.');
                }
            }],
        ]);

        $assignment = CoachMember::updateOrCreate([
            'coach_id' => $request->coach_id,
            'member_id' => $request->member_id,
        ]);

        $this->auditLogService->log(
            event: 'user.coach_assigned',
            module: 'assignments',
            auditable: $assignment,
            description: 'Coach ditugaskan ke Member.',
            after: ['coach_id' => $request->coach_id, 'member_id' => $request->member_id]
        );

        return back()->with('success', 'Coach berhasil ditugaskan ke Member.');
    }

    /**
     * Assign a guardian to a member.
     */
    public function assignGuardian(Request $request)
    {
        $request->validate([
            'guardian_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (! $user || $user->role !== 'guardian') {
                    $fail('User yang dipilih harus memiliki role Guardian.');
                }
            }],
            'member_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (! $user || $user->role !== 'member') {
                    $fail('User yang dipilih harus memiliki role Member.');
                }
            }],
            'can_approve_booking' => ['required', 'boolean'],
        ]);

        $relationship = UserRelationship::updateOrCreate([
            'guardian_id' => $request->guardian_id,
            'member_id' => $request->member_id,
        ], [
            'can_approve_booking' => $request->can_approve_booking,
        ]);

        $this->auditLogService->log(
            event: 'user.guardian_assigned',
            module: 'assignments',
            auditable: $relationship,
            description: 'Guardian ditugaskan ke Member.',
            after: [
                'guardian_id' => $request->guardian_id,
                'member_id' => $request->member_id,
                'can_approve_booking' => $request->can_approve_booking,
            ]
        );

        return back()->with('success', 'Guardian berhasil ditugaskan ke Member.');
    }

    /**
     * Remove coach assignment.
     */
    public function removeCoachAssignment(Request $request)
    {
        $request->validate([
            'coach_id' => ['required', 'exists:users,id'],
            'member_id' => ['required', 'exists:users,id'],
        ]);

        CoachMember::where('coach_id', $request->coach_id)
            ->where('member_id', $request->member_id)
            ->delete();

        return back()->with('success', 'Penugasan Coach berhasil dihapus.');
    }

    /**
     * Remove guardian assignment.
     */
    public function removeGuardianAssignment(Request $request)
    {
        $request->validate([
            'guardian_id' => ['required', 'exists:users,id'],
            'member_id' => ['required', 'exists:users,id'],
        ]);

        UserRelationship::where('guardian_id', $request->guardian_id)
            ->where('member_id', $request->member_id)
            ->delete();

        return back()->with('success', 'Hubungan Guardian berhasil dihapus.');
    }

    private function userPayload(User $user, array $roles, bool $avatarChanged): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'avatar_changed' => $avatarChanged,
            'roles' => array_values($roles),
        ];
    }
}
