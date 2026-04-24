<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $users = User::with(['roles', 'administeredKosts'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'kosts' => $user->administeredKosts->pluck('name')->toArray(),
            ]);

        return Inertia::render('Management/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $roles = Role::whereIn('name', ['superadmin', 'admin', 'owner'])->select('id', 'name')->get();
        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Management/Users/Form', [
            'roles' => $roles,
            'kosts' => $kosts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'kost_ids' => 'nullable|array',
            'kost_ids.*' => 'exists:m_kosts,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($validated['role_id']);

        // Assign kosts for admin role
        $role = Role::find($validated['role_id']);
        if ($role && $role->name === 'admin' && ! empty($validated['kost_ids'])) {
            $user->administeredKosts()->sync($validated['kost_ids']);
        }

        return to_route('management.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        $user->load(['roles', 'administeredKosts']);

        $roles = Role::whereIn('name', ['superadmin', 'admin', 'owner'])->select('id', 'name')->get();
        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Management/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->roles->first()?->id,
                'kost_ids' => $user->administeredKosts->pluck('id')->toArray(),
            ],
            'roles' => $roles,
            'kosts' => $kosts,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'kost_ids' => 'nullable|array',
            'kost_ids.*' => 'exists:m_kosts,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->roles()->sync([$validated['role_id']]);

        // Sync kosts for admin role
        $role = Role::find($validated['role_id']);
        if ($role && $role->name === 'admin') {
            $user->administeredKosts()->sync($validated['kost_ids'] ?? []);
        } else {
            $user->administeredKosts()->detach();
        }

        return to_route('management.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->roles()->detach();
        $user->administeredKosts()->detach();
        $user->delete();

        return to_route('management.users.index')->with('success', 'User berhasil dihapus.');
    }
}
