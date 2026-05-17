# Search Console Setup (yonoappsall.com)

1. Open [Google Search Console](https://search.google.com/search-console) and add property `https://yonoappsall.com`.
2. Verify ownership (recommended: DNS TXT record in your domain provider).
3. In [index.html](C:\New folder\lowcostweb\index.html), replace:
`REPLACE_WITH_SEARCH_CONSOLE_TOKEN`
with your real verification token from Google.
4. Redeploy the site.
5. In Search Console, submit sitemap:
`https://yonoappsall.com/sitemap.xml`
6. Use URL Inspection and request indexing for:
- `https://yonoappsall.com/`
- `https://yonoappsall.com/contact.html`
- `https://yonoappsall.com/privacy.html`
- `https://yonoappsall.com/terms.html`
- `https://yonoappsall.com/disclaimer.html`
