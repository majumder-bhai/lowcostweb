# cPanel Deployment Guide

## Step 1: Upload Files to cPanel

1. Go to **cPanel File Manager** or use **FTP/SFTP**
2. Upload all files to your **public_html** folder:
   - `index.html`
   - `script.js`
   - `styles.css`
   - `apps.json`
   - `.htaccess` (hidden file - enable "Show Hidden Files" in cPanel)
   - All other `.html` files
   - **Folder `api/`** with `apps.php` and `admin/login.php`
   - **`config.php`**
   - **`admin.php`**
   - `assets/` folder

## Step 2: Create MySQL Database

1. Go to **cPanel > MySQL Databases**
2. Create a new database (e.g., `yourdomain_yonoapps`)
3. Create a new user (e.g., `yourdomain_yonouser`) with a strong password
4. Add the user to the database with ALL privileges

## Step 3: Configure config.php

After uploading, edit `config.php` in cPanel File Manager:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'yourdomain_yonouser');      // Your MySQL username
define('DB_PASS', 'your_database_password');   // Your MySQL password
define('DB_NAME', 'yourdomain_yonoapps');      // Your database name
define('ADMIN_PASSWORD', 'change_me_to_strong_password');  // Admin panel password
define('ADMIN_SECRET', 'your_secret_key_min_16_chars_long');  // Secret key
```

Or set as environment variables in cPanel if available.

## Step 4: Initialize Database

1. Visit `https://yoursite.com/api/apps` in browser
2. It should return an empty JSON array `[]`
3. If you see an error, check config.php database settings

## Step 5: Test Admin Panel

1. Go to `https://yoursite.com/admin.php`
2. Click "Login" and enter your `ADMIN_PASSWORD`
3. You should see the admin dashboard
4. Click "Add App" to add apps to the database

## Step 6: Verify Apps Display

1. Visit `https://yoursite.com` main site
2. Apps should now load from the database
3. Download links should work (they point to the `url` field in each app)

## Step 7: Update Download Link Behavior

The download links in apps point to the `url` field. This can be:
- **External URL**: `https://play.google.com/store/apps/details?id=...`
- **Direct APK link**: `https://example.com/app.apk`
- **Referral link**: `https://referralsite.com/?code=...`

Currently set in `apps.json` - you can update via admin panel.

## Troubleshooting

### "404 Not Found" on admin.php or api/apps

- Check `.htaccess` is uploaded and `mod_rewrite` is enabled
- In cPanel: **Select PHP Version > Handler** should be `cgi` or `fpm`
- Try accessing `/api/apps.php` directly (without rewrite)

### "Database connection failed"

- Check database credentials in `config.php`
- Verify MySQL user has permissions
- In cPanel **phpMyAdmin**: Test connection manually

### Admin login not working

- Check `ADMIN_PASSWORD` is set correctly in `config.php`
- Clear browser cache/cookies
- Check browser console for errors (F12 > Console)

### Apps not loading

- Check if database is initialized (visit `/api/apps`)
- Check `apps.json` has valid JSON format
- Check PHP errors: cPanel > Errors log

## Security Notes

1. **Change `ADMIN_PASSWORD`** immediately
2. **Change `ADMIN_SECRET`** to a random 16+ character string
3. Consider using `.htaccess` to restrict `/api/` to POST/GET only
4. In production, use HTTPS (cPanel provides free SSL)
5. Backup your database regularly

## File Structure

```
public_html/
├── index.html
├── admin.php
├── config.php
├── .htaccess
├── script.js
├── styles.css
├── apps.json
├── api/
│   ├── apps.php
│   └── admin/
│       └── login.php
├── assets/
│   └── allappslogo/
└── [other files]
```

## API Endpoints

All API endpoints are accessible at:

- **GET** `/api/apps` - Get all apps (public)
- **POST** `/api/apps` - Add app (requires auth)
- **PUT** `/api/apps` - Update app (requires auth)
- **DELETE** `/api/apps` - Delete app (requires auth)
- **POST** `/api/admin/login` - Get admin token
- **GET** `/api/admin/session` - Check session

## Features

- ✅ Apps load from MySQL database
- ✅ Admin panel to manage apps
- ✅ Secure token-based authentication
- ✅ Automatic apps.json fallback
- ✅ Proper caching headers
- ✅ Security headers configured
- ✅ Works on any cPanel hosting
