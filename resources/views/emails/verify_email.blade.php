<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحقق من بريدك الإلكتروني</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌹 أكاديمية روز</div>
            <h2>تحقق من بريدك الإلكتروني</h2>
        </div>

        <div class="content">
            <p>مرحباً،</p>
            <p>شكراً لك على التسجيل في أكاديمية الوردة. لإكمال عملية التسجيل، يرجى الضغط على الرابط أدناه لتأكيد بريدك الإلكتروني:</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">تأكيد البريد الإلكتروني</a>
            </div>

            <p>إذا لم تتمكن من الضغط على الرابط، يرجى نسخ الرابط التالي ولصقه في متصفحك:</p>
            <p style="word-break: break-all; color: #666;">{{ $verificationUrl }}</p>

            <p><strong>ملاحظة:</strong> هذا الرابط صالح لمدة 24 ساعة فقط.</p>
        </div>

        <div class="footer">
            <p>إذا لم تقم بالتسجيل في موقعنا، يرجى تجاهل هذا البريد الإلكتروني.</p>
            <p>© 2025 أكاديمية الوردة. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
