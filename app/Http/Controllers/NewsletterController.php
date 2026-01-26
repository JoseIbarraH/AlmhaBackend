<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function sendTestEmail()
    {
        // Esta es la dirección de Mail-Tester que se queda cargando
        $testEmail = 'test-psn03xtrl@srv1.mail-tester.com';

        $data = ['title' => 'Prueba de Entregabilidad', 'content' => 'Contenido de mi newsletter.'];

        try {
            Mail::send([], [], function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Prueba desde Laravel y Stalwart')
                        // Texto plano para evitar que Mail-Tester te baje puntos
                        ->text('Hola, esta es una prueba de envío desde mi servidor autohospedado.');
            });

            return "¡Correo enviado con éxito! Revisa la página de Mail-Tester ahora.";
        } catch (\Exception $e) {
            \Log::info($e);
            return "Error al enviar: " . $e->getMessage();
        }
    }
}
