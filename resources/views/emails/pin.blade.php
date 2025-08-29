
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد الإلكتروني</title>
    <style>
        body {
            font-family: 'Cairo', Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .verification-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            transition: transform 0.3s ease;
        }
        .verification-button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌹 روز أكاديمي</h1>
            <p>تأكيد البريد الإلكتروني</p>
        </div>
        
        <div class="content">
            <h2>مرحباً بك في روز أكاديمي!</h2>
            <p>شكراً لك على التسجيل معنا. لإتمام عملية التسجيل، يرجى تأكيد بريدك الإلكتروني بالضغط على الزر أدناه:</p>
            
            <a href="{{ $verificationUrl }}" class="verification-button">
                تأكيد البريد الإلكتروني
            </a>
            
            <div class="warning">
                <strong>تنبيه:</strong> هذا الرابط صالح لمدة ساعة واحدة فقط
            </div>
            
            <p>إذا لم تقم بإنشاء حساب معنا، يرجى تجاهل هذا البريد الإلكتروني.</p>
        </div>
        
        <div class="footer">
            <p>© 2024 روز أكاديمي - جميع الحقوق محفوظة</p>
            <p>www.rose-academy.com</p>
        </div>
    </div>
</body>
</html>
