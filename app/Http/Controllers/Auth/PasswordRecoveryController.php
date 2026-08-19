<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordRecoveryController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/RecoverPassword');
    }

    public function findUser(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string|max:20',
        ]);

        $user = User::where('cedula', $request->cedula)->first();

        if (!$user) {
            return back()->withErrors(['cedula' => 'No se encontró ningún usuario con esta cédula.']);
        }

        if (!$user->is_active) {
            return back()->withErrors(['cedula' => 'Esta cuenta está desactivada. Contacte al administrador.']);
        }

        $questions = $user->securityQuestions()->orderBy('order')->get();

        if ($questions->isEmpty()) {
            return back()->withErrors(['cedula' => 'Este usuario no tiene preguntas de seguridad configuradas. Contacte al administrador para resetear su contraseña.']);
        }

        return Inertia::render('Auth/RecoverPassword', [
            'step' => 2,
            'userId' => $user->id,
            'userName' => $user->name,
            'questions' => $questions->map(fn($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'order' => $q->order,
            ]),
        ]);
    }

    public function verifyAndReset(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'answer_1' => 'required|string',
            'answer_2' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->is_active) {
            return back()->withErrors(['cedula' => 'Esta cuenta está desactivada.']);
        }

        $questions = $user->securityQuestions()->orderBy('order')->get();

        if ($questions->count() < 2) {
            return back()->withErrors(['cedula' => 'Error de seguridad. Contacte al administrador.']);
        }

        // Verify answers (compared lowercase and trimmed)
        $answer1Correct = Hash::check(strtolower(trim($request->answer_1)), $questions[0]->getRawOriginal('answer'));
        $answer2Correct = Hash::check(strtolower(trim($request->answer_2)), $questions[1]->getRawOriginal('answer'));

        if (!$answer1Correct || !$answer2Correct) {
            return back()->withErrors([
                'answers' => 'Las respuestas no coinciden con las registradas. Si no recuerdas tus respuestas, contacta al administrador para que resetee tu contraseña.',
            ]);
        }

        // All correct — reset password
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect('/login')->with('success', '¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva clave.');
    }
}