# QUICK START - cPanel Deployment

## 5 Minute Setup

### 1️⃣ Upload Files to cPanel

Use **File Manager** or **FTP** to upload everything from your project to `public_html/`:

```
✅ All HTML files (index.html, admin.php, etc.)
✅ script.js, styles.css
✅ config.php (IMPORTANT!)
✅ .htaccess (enable "Show Hidden Files" in File Manager)
✅ api/ folder with PHP files
✅ assets/ folder
✅ apps.json
```

### 2️⃣ Create Database in cPanel

1. Go to **cPanel → MySQL Databases**
2. Create database: `yourdomain_apps`
3. Create user: `yourdomain_user` with password
4. Add user to database (ALL privileges)

### 3️⃣ Configure config.php

Edit `config.php` in cPanel File Manager, update lines 2-6:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'yourdomain_user');              // ← Your MySQL username
define('DB_PASS', 'your_password_here');           // ← Your MySQL password
define('DB_NAME', 'yourdomain_apps');              // ← Your database name
define('ADMIN_PASSWORD', 'your_admin_pass_123');   // ← Your admin login password
```

### 4️⃣ Test & Login

Open browser:
- **Test DB**: Visit `https://yoursite.com/api/apps` → Should show `[]` or app list
- **Admin Panel**: Go to `https://yoursite.com/admin.php`
  - Click "Login"
  - Enter password from `ADMIN_PASSWORD`
  - Click "Add App" to manage apps

### 5️⃣ Done! 🎉

- Apps page loads from database
- Download links work (from `url` field)
- Admin can add/edit/delete apps via `/admin.php`

---

## Add Apps (2 Ways)

### Way 1: Admin Dashboard (Easy)
1. Go to `/admin.php` → Login
2. Click "Add App"
3. Fill form → Submit
4. Appears on homepage instantly

### Way 2: apps.json (Initial Load)
1. Keep `apps.json` in root
2. First load: apps import to database automatically
3. After that, edit via admin panel

---

## Download Link Configuration

Each app has a `url` field. Set it to:

**External URLs** (Most Common):
```
https://play.google.com/store/apps/details?id=com.app
https://referral.apkpure.com/?code=ABC123
https://example.com/download.apk
```

**Edit Download Link**:
1. Admin panel → Apps tab
2. Click "Edit" next to app
3. Change URL field
4. Save

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **404 on /admin.php** | Check `.htaccess` uploaded and mod_rewrite enabled |
| **DB connection error** | Verify credentials in `config.php` match cPanel database |
| **Apps not loading** | Visit `/api/apps` directly - should show JSON |
| **Admin login fails** | Check `ADMIN_PASSWORD` spelling in `config.php` |
| **Download link broken** | Check URL starts with `https://` or `http://` |

---

## Security

⚠️ **IMPORTANT**: Change these BEFORE going live:

1. **ADMIN_PASSWORD** - Use strong password (12+ chars)
2. **ADMIN_SECRET** - Use random string (16+ chars)

```php
define('ADMIN_PASSWORD', 'MyStr0ngP@ssw0rd123');
define('ADMIN_SECRET', 'aB3dFg9hIjKlMnOpQrStUvWxYz1234567890');
```

---

## File Locations

```
https://yoursite.com/
├── index.html (main page)
├── admin.php (📍 admin login here)
├── /api/apps (API endpoint)
└── /assets/allappslogo/ (app logos)
```

---

## Support

See `CPANEL_SETUP.md` for detailed guide.

Need help?
1. Check database credentials
2. Verify all files uploaded to public_html
3. Check error logs in cPanel
4. Ensure PHP version 7.4+

---

**Status**: ✅ Ready to deploy on cPanel
