<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a Almha!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #2c3e50;
        }

        .content {
            font-size: 16px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>¡Gracias por suscribirte!</h1>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>Gracias por unirte a nuestra comunidad. Estamos emocionados de tenerte con nosotros.</p>
            <p>Recibirás noticias, actualizaciones y contenido exclusivo directamente en tu bandeja de entrada.</p>
            <p>¡Esperamos que disfrutes de nuestro contenido!</p>
            <br>
            <p>Saludos,</p>
            <p>El equipo de Almha</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Almha. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>
