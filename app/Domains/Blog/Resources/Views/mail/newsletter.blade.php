<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $blog->translation->title ?? 'New Blog Post' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .content {
            padding: 20px 0;
            text-align: center;
        }

        .image-container {
            margin-bottom: 20px;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }

        .unsubscribe {
            color: #95a5a6;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Almha Newsletter</h2>
        </div>

        <div class="content">
            @if(isset($blog->image) && $blog->image)
                <div class="image-container">
                    <img src="{{ $blog->image }}" alt="{{ $blog->translation->title ?? 'Blog Image' }}">
                </div>
            @endif

            <h1 class="title">{{ $blog->translation->title ?? 'New Blog Post' }}</h1>

            <p>Hello {{ $subscriber->name ?? 'Subscriber' }},</p>
            <p>We have published a new blog post that might interest you.</p>

            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/blog/{{ $blog->slug }}" class="button">
                Read Full Article
            </a>
        </div>

        <div class="footer">
            <p>You received this email because you are subscribed to our newsletter.</p>
            <p>
                <a href="{{ url('/api/newsletter/unsubscribe?email=' . urlencode($subscriber->email)) }}"
                    class="unsubscribe">
                    Unsubscribe
                </a>
            </p>
        </div>
    </div>
</body>

</html>
