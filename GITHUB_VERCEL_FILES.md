# GitHub + Vercel Usable Files

Use this as the single reference for what to keep in your repo.

## Keep These (Required)

1. `index.html`
2. `styles.css`
3. `script.js`
4. `apps.json`
5. `privacy.html`
6. `terms.html`
7. `disclaimer.html`
8. `robots.txt`
9. `sitemap.xml`
10. `README.md`
11. `MIGRATION_CHECKLIST.md`

## Backend Files

1. `server.py`  
Keep this in GitHub if you want the Python backend version.

## Do Not Upload

1. `apps.db`  
Local runtime database file (generated automatically).
2. `__pycache__/` and `*.pyc`  
Python cache files.
3. `.env`  
If you create one for secrets.

## Recommended `.gitignore`

```gitignore
__pycache__/
*.pyc
apps.db
.env
.DS_Store
```

## Vercel Note

1. Static frontend files above can deploy to Vercel directly.
2. `server.py` is not a long-running Vercel server process.
3. For production with current backend logic:
- Use Vercel for frontend
- Use Render/Railway/Fly.io for Python backend
