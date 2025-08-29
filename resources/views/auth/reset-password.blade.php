<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 450px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            animation: fadeIn 0.5s ease-in-out;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #e74c3c;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        input[type="password"]:focus {
            border-color: #e74c3c;
            outline: none;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }
        .error {
            color: #e74c3c;
            font-size: 13px;
            margin-bottom: 10px;
            display: none;
        }
        button {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 8px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #c0392b;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(15px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<div class="container">
    <h2>إعادة تعيين كلمة المرور</h2>

    {{-- مكان عرض الرسائل --}}
    <div id="messageBox" style="display:none; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>

    <form id="resetForm">
        @csrf
        <input type="hidden" name="token" value="{{ request()->query('token') }}">
        <input type="hidden" name="email" value="{{ request()->query('email') }}">

        <label for="password">كلمة المرور الجديدة</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirmation">تأكيد كلمة المرور</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">تغيير كلمة المرور</button>
    </form>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    let form = e.target;
    let formData = new FormData(form);
    let messageBox = document.getElementById('messageBox');

    try {
        let response = await fetch("{{ url('/api/auth/reset-password') }}", {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": formData.get('_token')
            },
            body: formData
        });

        let result = await response.json();
        messageBox.style.display = 'block';

        if (response.ok) {
            messageBox.style.backgroundColor = '#e8f8f5';
            messageBox.style.color = '#27ae60';
            messageBox.innerText = result.message || 'تم تغيير كلمة المرور بنجاح';
            form.reset();
        } else {
            messageBox.style.backgroundColor = '#ffe6e6';
            messageBox.style.color = '#c0392b';

            if (result.errors) {
                messageBox.innerHTML = Object.values(result.errors).map(err => `<div>${err}</div>`).join('');
            } else {
                messageBox.innerText = result.message || 'حدث خطأ أثناء تغيير كلمة المرور';
            }
        }

    } catch (error) {
        messageBox.style.display = 'block';
        messageBox.style.backgroundColor = '#ffe6e6';
        messageBox.style.color = '#c0392b';
        messageBox.innerText = 'فشل الاتصال بالخادم';
    }
});
</script>
</body>
</html>
