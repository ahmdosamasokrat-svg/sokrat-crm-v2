<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>Sokrat Voice</title>
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: #09090d; }
        iframe { width: 100%; height: 100%; border: none; display: block; }
        .error-container { display: flex; align-items: center; justify-content: center; height: 100vh; color: #f87171; font-family: sans-serif; text-align: center; padding: 24px; }
    </style>
</head>
<body>
@if($embedUrl)
    <iframe src="{{ $embedUrl }}" allow="microphone *; autoplay *" referrerpolicy="no-referrer"></iframe>
@else
    <div class="error-container">
        <div>
            <h2>تعذر تحميل الهاتف</h2>
            <p>{{ $error ?? 'خطأ غير معروف' }}</p>
        </div>
    </div>
@endif
</body>
</html>
