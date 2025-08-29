<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>منصة التعلم - Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            color: white;
            text-align: center;
            padding: 100px 0;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .api-status {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="api-status">
        🟢 API Status: Active
    </div>

    <div class="container-fluid">
        <div class="hero-section">
            <div class="container">
                <h1 class="display-3 mb-4">🚀 Learning Platform API</h1>
                <p class="lead mb-5">منصة تعلم شاملة مع API متكامل يدعم اللغتين العربية والإنجليزية</p>

                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <a href="/docs" class="btn btn-light btn-lg mb-3 w-100">
                            📚 API Documentation
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/api" class="btn btn-outline-light btn-lg mb-3 w-100">
                            🔗 API Endpoints
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container py-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>🔐 Authentication</h4>
                        <p>نظام مصادقة متكامل مع Laravel Sanctum</p>
                        <ul class="list-unstyled">
                            <li>✅ تسجيل المستخدمين</li>
                            <li>✅ تسجيل الدخول</li>
                            <li>✅ التحقق من البريد الإلكتروني</li>
                            <li>✅ إعادة تعيين كلمة المرور</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>📖 Course Management</h4>
                        <p>إدارة شاملة للدورات والدروس</p>
                        <ul class="list-unstyled">
                            <li>✅ إنشاء وإدارة الدورات</li>
                            <li>✅ إضافة الدروس</li>
                            <li>✅ تقييم الدورات</li>
                            <li>✅ التعليقات والمفضلة</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>💳 Subscription & Payment</h4>
                        <p>نظام اشتراكات ومدفوعات متقدم</p>
                        <ul class="list-unstyled">
                            <li>✅ الاشتراك في الدورات</li>
                            <li>✅ معالجة المدفوعات</li>
                            <li>✅ إدارة الاشتراكات</li>
                            <li>✅ تقارير مالية</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="feature-card">
                        <h4>🌐 Multi-Language Support</h4>
                        <p>دعم كامل للغتين العربية والإنجليزية</p>
                        <div class="alert alert-info">
                            <strong>كيفية التحكم في اللغة:</strong><br>
                            أضف <code>Accept-Language: ar</code> للعربية<br>
                            أضف <code>Accept-Language: en</code> للإنجليزية
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="feature-card">
                        <h4>🧪 Built-in API Tester</h4>
                        <p>اختبر جميع APIs مباشرة من المتصفح</p>
                        <a href="/docs#test" class="btn btn-primary">
                            ابدأ الاختبار الآن
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <div class="feature-card">
                    <h3>📋 Available Endpoints</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Authentication:</strong>
                            <ul class="list-unstyled text-start">
                                <li>POST /auth/register</li>
                                <li>POST /auth/login</li>
                                <li>POST /auth/logout</li>
                                <li>POST /auth/verify-email</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Courses:</strong>
                            <ul class="list-unstyled text-start">
                                <li>GET /courses</li>
                                <li>POST /courses</li>
                                <li>GET /courses/{id}</li>
                                <li>PUT /courses/{id}</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Subscriptions:</strong>
                            <ul class="list-unstyled text-start">
                                <li>POST /courses/{id}/subscribe</li>
                                <li>DELETE /courses/{id}/unsubscribe</li>
                                <li>GET /my-subscriptions</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <strong>Management:</strong>
                            <ul class="list-unstyled text-start">
                                <li>GET /admin/users</li>
                                <li>GET /admin/payments</li>
                                <li>POST /admin/payments/{id}/accept</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4" style="background: rgba(0,0,0,0.1); color: white;">
        <p>&copy; 2025 Learning Platform API - Built with Laravel & Love 💙</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>