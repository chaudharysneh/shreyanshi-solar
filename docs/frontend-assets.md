# Frontend assets and deployment

Use `{{ crm_asset('css/example.css') }}` or `{{ crm_asset('js/example.js') }}`
for local stylesheets and scripts. The helper adds a content hash to the URL.
Changed contents receive a new URL even when deployment preserves timestamps.
Third-party CDN URLs stay unchanged.

The helper is registered by `AppServiceProvider`, so no Composer autoload
regeneration or frontend build is required for these plain CSS/JavaScript files.

## Deploying this change

Deploy all changed views, public assets, and these shared files together:

- `app/Providers/AppServiceProvider.php`
- `app/Helpers/assetHelper.php`
- `app/Support/FrontendAsset.php`
- `config/frontend_assets.php`

Then run from the live application directory:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Purge cached HTML in any reverse proxy/CDN. If the CDN ignores query strings,
purge its CSS/JavaScript cache too. Hard-refresh the browser after deployment.

## Hosting paths

`PUBLIC_PATH` is read by `config/frontend_assets.php`, not directly by views,
so it continues to work with Laravel configuration caching.

- When the domain serves Laravel's `public` directory directly, leave
  `PUBLIC_PATH` empty.
- If the application genuinely serves assets below a `/public` URL prefix,
  use `PUBLIC_PATH=public`.
- Laravel's `ASSET_URL` is supported for a dedicated asset origin. The
  `PUBLIC_PATH` prefix is appended to that origin, so do not specify the same
  prefix twice.

These settings describe the public URL, not an operating-system directory.
Rebuild configuration cache after changing them.

## Troubleshooting

In browser DevTools → Network, check the failing CSS/JavaScript URL:

- It should include `?v=` followed by a content hash.
- It should return HTTP 200 with CSS/JavaScript content, not a login page or HTML.
- A 404 means the file or public path is wrong; clearing Laravel caches cannot
  upload a missing file.

Missing assets do not crash Blade rendering, but they must still be deployed.
