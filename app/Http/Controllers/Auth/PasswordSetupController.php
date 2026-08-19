<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordSetupController extends Controller
{
    protected array $availableQuestions = [
        '¿Cuál es el nombre de tu primera mascota?',
        '¿En qué ciudad naciste?',
        '¿Cuál fue el nombre de tu mejor amigo(a) de la infancia?',
        '¿Cuál es el segundo nombre de tu madre?',
        '¿Cuál fue tu primer número de teléfono?',
        '¿Cuál es el nombre de tu escuela primaria?',
        '¿Cuál es tu comida favorita?',
        '¿Cuál fue el primer lugar donde trabajaste?',
    ];

    public function show()
    {
        if (!auth()->user()->must_change_password) {
            return redirect('/dashboard');
        }

        return Inertia::render('Auth/PasswordSetup', [
            'questions' => $this->availableQuestions,
            'userName' => auth()->user()->name,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'question_1' => 'required|string|max:255',
            'answer_1' => 'required|string|min:2|max:255',
            'question_2' => 'required|string|max:255|different:question_1',
            'answer_2' => 'required|string|min:2|max:255',
        ], [
            'question_2.different' => 'Las dos preguntas de seguridad deben ser diferentes.',
            'answer_1.min' => 'La respuesta debe tener al menos 2 caracteres.',
            'answer_2.min' => 'La respuesta debe tener al menos 2 caracteres.',
        ]);

        $user = auth()->user();

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Delete old security questions and create new ones
        $user->securityQuestions()->delete();

        SecurityQuestion::create([
            'user_id' => $user->id,
            'question' => $validated['question_1'],
            'answer' => strtolower(trim($validated['answer_1'])),
            'order' => 1,
        ]);

        SecurityQuestion::create([
            'user_id' => $user->id,
            'question' => $validated['question_2'],
            'answer' => strtolower(trim($validated['answer_2'])),
            'order' => 2,
        ]);

        return redirect('/dashboard')->with('success', '¡Configuración completada! Tu contraseña y preguntas de seguridad han sido establecidas.');
    }
}