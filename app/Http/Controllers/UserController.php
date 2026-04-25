<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('viewAny', User::class);

        $role = $request->query('role', '');

        if ($user->role === 'admin') {
            if ($role === 'admin') {
                $users = User::where([
                    ['id', '!=', $user->id],
                    ['role', 'admin']])
                    ->latest()
                    ->paginate(10);

                return view('users.index', compact('users'));
            }
            else {
                $users = User::where([
                    ['id', '!=', $user->id],
                    ['role', '!=', 'admin']])
                    ->latest()
                    ->paginate(10);

                return view('users.index', compact('users'));
            }
        } else {
            redirect('home');
        }
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', User::class);

        return view('users.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email', Rule::unique('users')],
            'password' => ['required', 'string', 'max:255', 'min:6', "same:confirm-password"],
            'role' => ['required']
        ], [
            'name.required' => 'The user name is required.',
            'email.required' => 'The user email is required.',
            'password.required' => "The user password is required.",
            'password.same' => "The confirmation password does not match.",
            'role.required' => 'The user role is required.',
        ]);

        $oUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index', ['role' => $oUser->role])
            ->with('success', 'Created '.$oUser->role.' successfully.');
    }

    public function edit(User $oUser)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $user);

        return view('users.edit', compact('oUser', 'user'));
    }

    public function update(Request $request, User $oUser)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'email', Rule::unique('users')->ignore($oUser->id)],
            'role' => ['required'],
        ], [
            'name.required' => 'The user name is required.',
            'email.required' => 'The user email is required.',
            'role.required' => 'The user role is required',
        ]);

        $oUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($oUser->id === $user->id) {
            return redirect()->route('home')
                ->with('success', 'User account updated successfully.');
        }
        else {
            return redirect()->route('users.index', ['role' => $oUser->role])
                ->with('success', 'Updated '.$oUser->role.' successfully.');
        }
    }

    public function destroy(User $oUser)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('delete', $user);

        $role = $oUser->role;
        $oUser->delete();

        return redirect()->route('users.index', ['role' => $role])
            ->with('success', 'Deleted '.$role.' successfully.');
    }

    
    public function updatePassword(User $oUser)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $user);

        return view('users.updatePassword', compact('oUser'));
    }

    public function savePassword(Request $request, User $oUser)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255', 'min:6', "same:confirm-password"],
        ], [
            'password.required' => "The user password is required.",
            'password.same' => "The confirmation password does not match.",
        ]);

        $oUser->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.edit', compact('oUser', 'user'))
            ->with('success', 'User Password updated successfully.');
    }
}
