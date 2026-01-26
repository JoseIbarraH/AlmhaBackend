<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function sendTestEmail()
    {
        // Busca MAIL_TEST_RECIPIENT en el .env, si no existe usa el correo por defecto
        $testEmail = env('MAIL_TEST_RECIPIENT', 'test@test.com');

        try {
            \Mail::send([], [], function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Prueba desde Laravel y Stalwart')
                    ->text('Hola, esta es una prueba de envío desde mi servidor autohospedado.');
            });

            return "¡Correo enviado con éxito a {$testEmail}! Revisa la página de Mail-Tester ahora.";
        } catch (\Exception $e) {
            \Log::error("Error en envío de prueba: " . $e->getMessage());
            return "Error al enviar: " . $e->getMessage();
        }
    }
}
