<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>SokratCRM - تسجيل الدخول</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background: #f3f5f9;
            color: #111827;
            display: grid;
            place-items: center;
        }
        .login-card {
            width: min(420px, calc(100vw - 32px));
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
        }
        .brand {
            text-align: center;
            margin-bottom: 26px;
        }
        .brand h1 {
            margin: 0;
            color: #e11d2e;
            font-size: 34px;
            font-weight: 900;
        }
        .brand p {
            margin: 8px 0 0;
            color: #8a94a6;
            font-size: 14px;
        }
        label {
            display: block;
            margin: 16px 0 8px;
            font-weight: 700;
            color: #374151;
        }
        input {
            width: 100%;
            height: 48px;
            border: 1px solid #d9e0ea;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            outline: none;
        }
        input:focus {
            border-color: #e11d2e;
            box-shadow: 0 0 0 4px rgba(225, 29, 46, .08);
        }
        button {
            width: 100%;
            margin-top: 24px;
            height: 50px;
            border: 0;
            border-radius: 12px;
            background: #e11d2e;
            color: #fff;
            font-weight: 900;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(225, 29, 46, .25);
        }
        .error {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 14px;
            font-weight: 700;
        }
    </style>
</head>
<body>
@include('partials.page-loader')
    <form class="login-card" method="post" action="{{ route('login.post') }}">
        @csrf
        <div class="brand">
            <h1>SokratCRM</h1>
            <p>لوحة التحكم</p>
        </div>

        @if ($errors->any())
            <div class="error">بيانات الدخول غير صحيحة</div>
        @endif

        <label>اسم المستخدم</label>
        <input name="username" autocomplete="username" autofocus>

        <label>كلمة المرور</label>
        <input name="password" type="password" autocomplete="current-password">

        <button type="submit">تسجيل الدخول</button>
    </form>
</body>
</html>
