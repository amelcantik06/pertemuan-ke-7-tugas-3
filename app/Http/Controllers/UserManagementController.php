<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::orderByRaw("CASE role WHEN 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'kasir'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user = DB::transaction(function () use ($user, $validated): User {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($user->role === 'admin' && $validated['role'] !== 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'Setidaknya harus ada satu akun admin aktif.',
                ]);
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            return $user;
        });

        if ($user->is(auth()->user()) && $user->role !== 'admin') {
            return redirect()->route('kasir')->with('success', 'Profil dan role akun Anda berhasil diperbarui.');
        }

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($user): void {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($user->role === 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'Setidaknya harus ada satu akun admin aktif.',
                ]);
            }

            if ($user->sales()->exists() || $user->stockReceipts()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Akun ini memiliki riwayat transaksi atau stok masuk dan tidak dapat dihapus.',
                ]);
            }

            $user->delete();
        });

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
