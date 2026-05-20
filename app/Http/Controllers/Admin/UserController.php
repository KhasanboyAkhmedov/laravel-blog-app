<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('roles')->paginate(15),
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);

        $user->syncRoles([$request->role]);

        return back()->with('message', 'Role updated.');
    }
}
