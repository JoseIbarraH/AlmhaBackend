<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $blog->translation->title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #eaeaea;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eeeeee;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
        }

        .image-container {
            text-align: center;
            margin: 20px 0;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .content {
            font-size: 16px;
            color: #555;
            padding: 10px 0;
        }

        .btn-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .btn {
            background-color: #3498db;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
        }

        .unsubscribe {
            color: #999;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Nueva publicación en Almha</h1>
        </div>

        <div class="image-container">
            @if($blog->image)
                <img src="{{ asset($blog->image) }}" alt="{{ $blog->translation->title }}">
            @endif
        </div>

        <div class="content">
            <h2 style="color: #333;">{{ $blog->translation->title }}</h2>
            <p>
                {{ Str::limit(strip_tags($blog->translation->content), 200) }}
            </p>
        </div>

        <div class="btn-container">
            <a href="{{ config('app.frontend_url_client') }}/en/blog/{{ $blog->slug }}" class="btn">Leer artículo completo</a>
        </div>

        <div class="content">
            <p>Esperamos que lo disfrutes,</p>
            <p>El equipo de Almha</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Almha. Todos los derechos reservados.<br>
            <br>
            {{-- <a href="#" class="unsubscribe">Darse de baja</a> --}}
        </div>
    </div>
</body>

</html>
