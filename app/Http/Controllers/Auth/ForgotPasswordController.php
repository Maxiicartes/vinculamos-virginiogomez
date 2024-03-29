<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Models\Usuarios;
use Mail;
use Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{

    public function showForgetPasswordForm()
    {
        return view('auth.passwords.email');
    }

    public function submitForgetPasswordForm(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:usuarios,usua_email',
            ],
            [
                'email.required' => 'El correo electrónico es requerido.',
                'email.email' => 'El correo debe ser válido.',
                'email.exists' => 'El correo  no se encuentra registrado.',
            ]
        );
        try {
            $token = Str::random(64);

            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            Mail::send('email.forgetPassword', ['token' => $token], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password');
            });

            return back()->with('message', '¡Hemos enviado el enlace para restablecer tu contraseña por correo electrónico!');
        } catch (\Exception $e) {
            // Manejar errores de base de datos o correo
            return back()->with('error', 'Ocurrió un error al procesar la solicitud. Por favor, intenta de nuevo más tarde.');
        }

    }

    public function showResetPasswordForm($token)
    {
        return view('auth.passwords.forgetPasswordLink', ['token' => $token]);
    }

    public function submitResetPasswordForm(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:usuarios,usua_email',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required'
            ],
            [
                'email.required' => 'El correo electrónico es requerido.',
                'email.email' => 'El correo debe ser válido.',
                'email.exists' => 'El correo no se encuentra registrado.',
                'password.required' => 'La contraseña es requerida.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'password.confirmed' => 'Las contraseñas deben ser iguales.',
                'password_confirmation.required' => 'Debe confirmar la contraseña.',
            ]

        );

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = Usuarios::where('usua_email', $request->email)
            ->update(['usua_clave' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect('/')->with('message', 'Your password has been changed!');
    }
}