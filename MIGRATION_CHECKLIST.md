# Migration Checklist: GitHub + Vercel (Later Upgrade)

This checklist helps you migrate safely when you decide to deploy publicly.

## Phase 1: Prepare Repository

1. Initialize git if needed.
2. Add a `.gitignore` file.
3. Ensure secrets are not in code.
4. Commit current working version.

### Recommended `.gitignore`

```gitignore
__pycache__/
*.pyc
apps.db
.env
.DS_Store
```

## Phase 2: Push to GitHub

1. Create a new GitHub repository.
2. Add remote.
3. Push your main branch.

## Phase 3: Choose Deployment Strategy

Pick one strategy:

1. Split deployment (recommended easiest):
- Frontend on Vercel
- Python backend on Render/Railway/Fly.io

2. Full Vercel migration:
- Convert Python server to serverless `api/*.py` handlers
- Keep frontend static files in same project

## Phase 4: If You Choose Split Deployment

1. Deploy backend first on Render/Railway/Fly.io.
2. Set environment variables on backend host:
- `ADMIN_PASSWORD`
- `ADMIN_SECRET`
- `TOKEN_TTL_SECONDS`
- `PORT` (if required by host)
3. Enable persistent disk/database support.
4. Update frontend API base URL in `script.js`.
5. Deploy frontend to Vercel.

## Phase 5: If You Choose Full Vercel Migration (Later)

1. Replace `server.py` long-running server with serverless endpoints.
2. Add `vercel.json` routes if needed.
3. Move DB to managed service (Supabase/Postgres/PlanetScale/etc.).
4. Update frontend fetch calls to new serverless endpoints.
5. Test admin login, add/import/reset/export end-to-end.

## Phase 6: Production Hardening

1. Use strong password and random secret values.
2. Add HTTPS-only cookies or secure token storage strategy.
3. Add monitoring/alerts for `/api/health`.
4. Add backup strategy for app data.
5. Add rate limits and audit logging for admin actions.

## Phase 7: SEO and Trust Finalization

1. Replace placeholder domain in:
- `index.html` canonical/OG URL
- `robots.txt` sitemap URL
- `sitemap.xml` URLs
2. Add real favicon and social preview image.
3. Review Privacy/Terms/Disclaimer content.

## Quick Pre-Launch Test List

1. Public can browse/search/filter/sort apps.
2. Admin panel hidden until lock login.
3. Wrong password is rejected.
4. Admin can add app with logo.
5. Import/export/reset work.
6. Reload keeps shared data.
7. Mobile layout works.

## Rollback Safety

Before each migration step:

1. Create a git tag or commit checkpoint.
2. Keep one known-working deployment live.
3. Migrate in small steps (backend first, then frontend).
