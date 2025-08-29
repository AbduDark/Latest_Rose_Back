
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توثيق APIs - منصة التعلم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #2c3e50; color: white; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; }
        .sidebar a:hover { color: #3498db; }
        .endpoint { border: 1px solid #ddd; border-radius: 8px; margin: 15px 0; padding: 15px; }
        .method-get { border-left: 5px solid #28a745; }
        .method-post { border-left: 5px solid #007bff; }
        .method-put { border-left: 5px solid #ffc107; }
        .method-delete { border-left: 5px solid #dc3545; }
        .badge-get { background-color: #28a745; }
        .badge-post { background-color: #007bff; }
        .badge-put { background-color: #ffc107; }
        .badge-delete { background-color: #dc3545; }
        .test-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .response-example { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; }
        .lang-toggle { position: fixed; top: 20px; right: 20px; z-index: 1000; }
    </style>
</head>
<body>
    <div class="lang-toggle">
        <button id="langToggle" class="btn btn-outline-primary">EN</button>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar p-3">
                <h4 class="mb-4">📚 API Documentation</h4>
                <ul class="list-unstyled">
                    <li><a href="#auth" class="d-block py-2">🔐 Authentication</a></li>
                    <li><a href="#courses" class="d-block py-2">📖 Courses</a></li>
                    <li><a href="#lessons" class="d-block py-2">📝 Lessons</a></li>
                    <li><a href="#subscriptions" class="d-block py-2">💳 Subscriptions</a></li>
                    <li><a href="#ratings" class="d-block py-2">⭐ Ratings</a></li>
                    <li><a href="#comments" class="d-block py-2">💬 Comments</a></li>
                    <li><a href="#favorites" class="d-block py-2">❤️ Favorites</a></li>
                    <li><a href="#payments" class="d-block py-2">💰 Payments</a></li>
                    <li><a href="#users" class="d-block py-2">👥 Users</a></li>
                    <li><a href="#test" class="d-block py-2">🧪 API Tester</a></li>
                </ul>
            </div>

            <!-- Main content -->
            <div class="col-md-9 p-4">
                <div class="mb-4">
                    <h1 class="text-primary">📚 Learning Platform API Documentation</h1>
                    <p class="lead">Complete API reference for the Laravel Learning Platform</p>
                    <div class="alert alert-info">
                        <strong>Base URL:</strong> <code id="baseUrl">{{url('/')}}/api</code><br>
                        <strong>Language Control:</strong> Use <code>Accept-Language</code> header with values <code>ar</code> or <code>en</code>
                    </div>
                </div>

                <!-- Authentication Section -->
                <section id="auth" class="mb-5">
                    <h2>🔐 Authentication</h2>
                    
                    <div class="endpoint method-post">
                        <h5><span class="badge badge-post">POST</span> /auth/register</h5>
                        <p>Register a new user</p>
                        <h6>Request Body:</h6>
                        <pre class="response-example"><code>{
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "01234567890",
  "gender": "male"
}</code></pre>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="POST" 
                                data-url="/auth/register" 
                                data-body='{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123","phone":"01234567890","gender":"male"}'>
                            Test API
                        </button>
                    </div>

                    <div class="endpoint method-post">
                        <h5><span class="badge badge-post">POST</span> /auth/login</h5>
                        <p>Login user</p>
                        <h6>Request Body:</h6>
                        <pre class="response-example"><code>{
  "email": "ahmed@example.com",
  "password": "password123"
}</code></pre>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="POST" 
                                data-url="/auth/login" 
                                data-body='{"email":"admin@example.com","password":"password"}'>
                            Test API
                        </button>
                    </div>

                    <div class="endpoint method-post">
                        <h5><span class="badge badge-post">POST</span> /auth/logout</h5>
                        <p>Logout user (requires authentication)</p>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="POST" 
                                data-url="/auth/logout" 
                                data-auth="true">
                            Test API
                        </button>
                    </div>
                </section>

                <!-- Courses Section -->
                <section id="courses" class="mb-5">
                    <h2>📖 Courses</h2>
                    
                    <div class="endpoint method-get">
                        <h5><span class="badge badge-get">GET</span> /courses</h5>
                        <p>Get all courses</p>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="GET" 
                                data-url="/courses">
                            Test API
                        </button>
                    </div>

                    <div class="endpoint method-get">
                        <h5><span class="badge badge-get">GET</span> /courses/{id}</h5>
                        <p>Get course details</p>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="GET" 
                                data-url="/courses/1">
                            Test API
                        </button>
                    </div>

                    <div class="endpoint method-post">
                        <h5><span class="badge badge-post">POST</span> /courses</h5>
                        <p>Create new course (Admin only)</p>
                        <h6>Request Body:</h6>
                        <pre class="response-example"><code>{
  "title": "دورة البرمجة",
  "description": "تعلم البرمجة من الصفر",
  "price": 99.99,
  "category": "programming",
  "level": "beginner"
}</code></pre>
                        <button class="btn btn-sm btn-primary test-endpoint" 
                                data-method="POST" 
                                data-url="/courses" 
                                data-auth="true"
                                data-body='{"title":"Test Course","description":"Test Description","price":99.99,"category":"programming","level":"beginner"}'>
                            Test API
                        </button>
                    </div>
                </section>

                <!-- API Tester Section -->
                <section id="test" class="test-section">
                    <h2>🧪 API Tester</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Method:</label>
                                <select id="testMethod" class="form-select">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Endpoint:</label>
                                <input type="text" id="testUrl" class="form-control" placeholder="/api/courses">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Language:</label>
                                <select id="testLang" class="form-select">
                                    <option value="ar">العربية</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="useAuth">
                                    <label class="form-check-label" for="useAuth">
                                        Use Authentication
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Request Body (JSON):</label>
                                <textarea id="testBody" class="form-control" rows="5" placeholder='{"key": "value"}'></textarea>
                            </div>
                            <button id="testBtn" class="btn btn-primary">Send Request</button>
                        </div>
                        <div class="col-md-6">
                            <h5>Response:</h5>
                            <div id="testResponse" class="response-example" style="min-height: 300px;">
                                Response will appear here...
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script>
        let authToken = localStorage.getItem('auth_token') || '';
        let currentLang = 'ar';

        // Language toggle
        document.getElementById('langToggle').addEventListener('click', function() {
            currentLang = currentLang === 'ar' ? 'en' : 'ar';
            this.textContent = currentLang === 'ar' ? 'EN' : 'AR';
            document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
            document.documentElement.lang = currentLang;
            document.getElementById('testLang').value = currentLang;
        });

        // Test endpoint buttons
        document.querySelectorAll('.test-endpoint').forEach(button => {
            button.addEventListener('click', function() {
                const method = this.dataset.method;
                const url = this.dataset.url;
                const needsAuth = this.dataset.auth === 'true';
                const body = this.dataset.body;

                document.getElementById('testMethod').value = method;
                document.getElementById('testUrl').value = url;
                document.getElementById('useAuth').checked = needsAuth;
                if (body) {
                    document.getElementById('testBody').value = body;
                }
                
                // Scroll to test section
                document.getElementById('test').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Main test button
        document.getElementById('testBtn').addEventListener('click', async function() {
            const method = document.getElementById('testMethod').value;
            const url = document.getElementById('testUrl').value;
            const useAuth = document.getElementById('useAuth').checked;
            const body = document.getElementById('testBody').value;
            const lang = document.getElementById('testLang').value;

            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Accept-Language': lang
            };

            if (useAuth && authToken) {
                headers['Authorization'] = `Bearer ${authToken}`;
            }

            const options = {
                method: method,
                headers: headers
            };

            if (body && method !== 'GET') {
                options.body = body;
            }

            try {
                const baseUrl = document.getElementById('baseUrl').textContent;
                const response = await fetch(baseUrl + url, options);
                const result = await response.json();

                // Store auth token if login was successful
                if (url === '/auth/login' && result.token) {
                    authToken = result.token;
                    localStorage.setItem('auth_token', authToken);
                }

                document.getElementById('testResponse').innerHTML = 
                    `<strong>Status: ${response.status}</strong><br><br>` +
                    '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('testResponse').innerHTML = 
                    `<strong style="color: #ff6b6b;">Error:</strong><br><pre>${error.message}</pre>`;
            }
        });

        // Auto-fill auth token from localStorage
        if (authToken) {
            console.log('Auth token loaded from localStorage');
        }
    </script>
</body>
</html>
