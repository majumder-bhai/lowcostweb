<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - YONO APPS ALL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0c1017;
            color: #e8edf8;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1a1f2e 0%, #2d3748 100%);
            border-radius: 8px;
        }
        
        h1 {
            font-size: 28px;
        }
        
        .logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: #1a1f2e;
            border-radius: 8px;
            border: 1px solid #2d3748;
        }
        
        .login-container h2 {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input[type="password"],
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            background: #0c1017;
            border: 1px solid #2d3748;
            border-radius: 6px;
            color: #e8edf8;
            font-family: inherit;
            font-size: 14px;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #2d3748;
        }
        
        .tab-btn {
            background: none;
            border: none;
            color: #9ca3af;
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 600;
            border-bottom: 2px solid transparent;
        }
        
        .tab-btn.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .message {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.error {
            background: #7f1d1d;
            color: #fecaca;
            border: 1px solid #991b1b;
            display: block;
        }
        
        .message.success {
            background: #1f2937;
            color: #86efac;
            border: 1px solid #22c55e;
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #2d3748;
        }
        
        th {
            background: #1a1f2e;
            font-weight: 600;
        }
        
        tr:hover {
            background: #111827;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #2d3748;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="loginForm" style="display: none;">
            <div class="login-container">
                <h2>Admin Login</h2>
                <div id="loginMessage" class="message"></div>
                <form onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
        
        <div id="dashboard" style="display: none;">
            <header>
                <h1>Admin Panel</h1>
                <button class="logout-btn" onclick="handleLogout()">Logout</button>
            </header>
            
            <div id="mainMessage" class="message"></div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('apps')">Apps</button>
                <button class="tab-btn" onclick="switchTab('add')">Add App</button>
            </div>
            
            <div id="apps" class="tab-content active">
                <h2>Manage Apps</h2>
                <div id="appsList" class="loading">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div id="add" class="tab-content">
                <h2>Add New App</h2>
                <form onsubmit="handleAddApp(event)" style="max-width: 600px;">
                    <div class="form-group">
                        <label for="name">App Name *</label>
                        <input type="text" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" required>
                            <option value="">Select Category</option>
                            <option value="All Apps">All Apps</option>
                            <option value="New Apps">New Apps</option>
                            <option value="Best Apps">Best Apps</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bonus">Sign-up Bonus *</label>
                        <input type="text" id="bonus" placeholder="e.g., Sign-up Bonus: Rs 75" required>
                    </div>
                    <div class="form-group">
                        <label for="withdraw">Min Withdraw *</label>
                        <input type="text" id="withdraw" placeholder="e.g., Min Withdraw: Rs 100" required>
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating (1-5) *</label>
                        <input type="number" id="rating" min="1" max="5" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label for="url">App URL *</label>
                        <input type="text" id="url" placeholder="https://..." required>
                    </div>
                    <div class="form-group">
                        <label for="logo">Logo URL or Data URL *</label>
                        <textarea id="logo" placeholder="https://... or data:image/..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add App</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const API_BASE = window.location.origin;
        
        function showMessage(msg, type = 'success', elementId = 'mainMessage') {
            const el = document.getElementById(elementId);
            el.textContent = msg;
            el.className = `message ${type}`;
            if (type === 'success') {
                setTimeout(() => el.className = 'message', 3000);
            }
        }
        
        async function handleLogin(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            
            try {
                const res = await fetch(`${API_BASE}/api/admin/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password })
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    showMessage(data.error || 'Login failed', 'error', 'loginMessage');
                    return;
                }
                
                localStorage.setItem('adminToken', data.token);
                document.getElementById('password').value = '';
                showDashboard();
                loadApps();
            } catch (err) {
                showMessage('Network error: ' + err.message, 'error', 'loginMessage');
            }
        }
        
        function handleLogout() {
            localStorage.removeItem('adminToken');
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('dashboard').style.display = 'none';
        }
        
        function showDashboard() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('dashboard').style.display = 'block';
        }
        
        function switchTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(name).classList.add('active');
            event.target.classList.add('active');
        }
        
        async function loadApps() {
            const token = localStorage.getItem('adminToken');
            if (!token) return;
            
            try {
                const res = await fetch(`${API_BASE}/api/apps`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                
                const apps = await res.json();
                
                let html = '<table><thead><tr><th>Name</th><th>Category</th><th>Rating</th><th>Actions</th></tr></thead><tbody>';
                
                apps.forEach(app => {
                    html += `<tr>
                        <td>${app.name}</td>
                        <td>${app.category}</td>
                        <td>${app.rating}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-secondary btn-sm" onclick="editApp(${app.id})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteApp(${app.id})">Delete</button>
                            </div>
                        </td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                document.getElementById('appsList').innerHTML = html;
            } catch (err) {
                document.getElementById('appsList').innerHTML = `<p style="color: #ef4444;">Error loading apps: ${err.message}</p>`;
            }
        }
        
        async function handleAddApp(e) {
            e.preventDefault();
            const token = localStorage.getItem('adminToken');
            
            const app = {
                name: document.getElementById('name').value,
                category: document.getElementById('category').value,
                bonus: document.getElementById('bonus').value,
                withdraw: document.getElementById('withdraw').value,
                rating: parseFloat(document.getElementById('rating').value),
                url: document.getElementById('url').value,
                logo: document.getElementById('logo').value
            };
            
            try {
                const res = await fetch(`${API_BASE}/api/apps`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(app)
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    showMessage(data.error || 'Failed to add app', 'error');
                    return;
                }
                
                showMessage('App added successfully!', 'success');
                e.target.reset();
                document.querySelector('.tab-btn').click();
                loadApps();
            } catch (err) {
                showMessage('Network error: ' + err.message, 'error');
            }
        }
        
        async function deleteApp(id) {
            if (!confirm('Are you sure you want to delete this app?')) return;
            
            const token = localStorage.getItem('adminToken');
            
            try {
                const res = await fetch(`${API_BASE}/api/apps?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    showMessage(data.error || 'Failed to delete app', 'error');
                    return;
                }
                
                showMessage('App deleted successfully!', 'success');
                loadApps();
            } catch (err) {
                showMessage('Network error: ' + err.message, 'error');
            }
        }
        
        // Initialize
        (async function init() {
            const token = localStorage.getItem('adminToken');
            
            if (token) {
                // Verify token
                try {
                    const res = await fetch(`${API_BASE}/api/admin/session`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    
                    if (res.ok) {
                        showDashboard();
                        loadApps();
                    } else {
                        localStorage.removeItem('adminToken');
                        document.getElementById('loginForm').style.display = 'block';
                    }
                } catch (err) {
                    document.getElementById('loginForm').style.display = 'block';
                }
            } else {
                document.getElementById('loginForm').style.display = 'block';
            }
        })();
    </script>
</body>
</html>
