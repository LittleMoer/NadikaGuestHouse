<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:owner,admin',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:owner,admin',
        ]);

        // Prevent demoting the last owner
        if ($user->role === 'owner' && $validated['role'] !== 'owner') {
            $ownersCount = User::where('role', 'owner')->count();
            if ($ownersCount <= 1) {
                return back()->with('error', 'Tidak bisa mengubah role. Minimal harus ada 1 Owner.');
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated');
    }

    public function destroy(User $user)
    {
        // prevent self-delete if only one owner exists
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        if ($user->role === 'owner') {
            $ownersCount = User::where('role', 'owner')->count();
            if ($ownersCount <= 1) {
                return back()->with('error', 'Tidak bisa menghapus Owner terakhir.');
            }
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted');
    }
}
