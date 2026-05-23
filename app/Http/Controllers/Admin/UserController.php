<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('roles')->latest()->paginate(15),
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        ActivityLoggerService::logRaw(
            action:  'user_created',
            model:   User::class,
            modelId: $user->id,
            payload: [
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $validated['role'],
                'created_by' => auth()->user()->email,
            ],
        );

        return back()->with('message', "User {$user->name} created.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);

        $oldRoles = $user->getRoleNames()->toArray();

        $user->syncRoles([$request->role]);

        ActivityLoggerService::logRaw(
            action:  'role_changed',
            model:   User::class,
            modelId: $user->id,
            payload: [
                'user'      => $user->email,
                'old_roles' => $oldRoles,
                'new_role'  => $request->role,
                'changed_by'=> auth()->user()->email,
            ],
        );

        return back()->with('message', 'Role updated.');
    }
}
