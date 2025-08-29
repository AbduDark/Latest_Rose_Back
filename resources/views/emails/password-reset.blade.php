<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .content {
            line-height: 1.6;
            color: #333;
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-top: 30px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌹 أكاديمية الوردة</div>
            <h2>إعادة تعيين كلمة المرور</h2>
        </div>

        <div class="content">
            <p>مرحباً {{ $user->name ?? '' }}،</p>
            <p>تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في أكاديمية الوردة.</p>

            <div class="warning">
                <strong>تنبيه أمني:</strong> إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني وتأكد من أمان حسابك.
            </div>

            <p>للمتابعة مع إعادة تعيين كلمة المرور، اضغط على الرابط أدناه:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">إعادة تعيين كلمة المرور</a>
            </div>

            <p>إذا لم تتمكن من الضغط على الرابط، يرجى نسخ الرابط التالي ولصقه في متصفحك:</p>
            <p style="word-break: break-all; color: #666;">{{ $resetUrl }}</p>

            <p><strong>ملاحظة مهمة:</strong></p>
            <ul>
                <li>هذا الرابط صالح لمدة 60 دقيقة فقط</li>
                <li>لا تشارك هذا الرابط مع أي شخص آخر</li>
                <li>سيتم تسجيل خروجك من جميع الأجهزة بعد تغيير كلمة المرور</li>
            </ul>
        </div>

        <div class="footer">
            <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.</p>
            <p>© 2025 أكاديمية الوردة. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
