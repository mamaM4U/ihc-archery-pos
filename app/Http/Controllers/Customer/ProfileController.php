<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Customer/Profile', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => ['nullable', 'confirmed', Password::min(6)],
        ]);

        // Validate current password if changing password
        if (filled($validated['current_password'])) {
            if (! Hash::check($validated['current_password'], $customer->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini salah.']);
            }
        }

        $customer->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $customer->address,
        ]);

        if (filled($validated['new_password'] ?? null)) {
            $customer->update(['password' => $validated['new_password']]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
