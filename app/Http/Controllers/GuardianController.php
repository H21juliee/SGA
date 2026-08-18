<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string',
        ]);

        $guardian = Guardian::where('cedula', $request->cedula)->first();

        return response()->json([
            'guardian' => $guardian
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cedula' => ['required', 'string', 'regex:/^[VEP]-\d{6,10}$/i', 'unique:guardians,cedula'],
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'regex:/^0[24]\d{2}-\d{7}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
        ]);

        $guardian = Guardian::create($validated);

        return response()->json([
            'message' => 'Representante creado exitosamente',
            'guardian' => $guardian
        ]);
    }
}