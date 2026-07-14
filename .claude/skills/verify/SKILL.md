---
name: verify
description: How to build, run, and drive this app (Laravel + Vite + MySQL) to observe a change for real.
---

## Build & run

```bash
npm run build              # or `npm run dev` for a live Vite dev server
php artisan serve --port=8123   # avoid 8000, Laragon's vhost may already use it
```

DB is a real local MySQL (`absensi_face`, root/no password) already migrated — do NOT
`migrate:fresh`. Check current state first with `php artisan migrate:status` /
`php artisan tinker --execute="..."`.

**This DB often holds live-ish settings** (e.g. `Pengaturan::get()->lokasi_lat/lng/radius_meter`
is the real GPS gate for student attendance). Read the current value with tinker before any
test that would submit a form touching `Pengaturan` — don't submit test data that overwrites it;
verify the JS/UI behavior without hitting the real save endpoint, or restore the original value
after.

## Auth for verification

Seeded admin: `admin@smk.test` / `password` (see `database/seeders/DatabaseSeeder.php`).
Admin-only pages live under `role:admin` middleware, e.g. `/pengaturan`.

## Driving the browser

No Playwright devDependency in this repo. Install it ad hoc without touching
`package.json`/lockfile:

```bash
npm install --no-save playwright
npx playwright install chromium   # only needed once per machine
```

Write throwaway scripts as `.cjs` inside the **project directory** (not the scratchpad) —
`package.json` has `"type": "module"`, so a plain `.js` file there is parsed as ESM and
`require()` fails; `node` also resolves `require('playwright')` relative to the script's own
directory, so a script outside the repo can't find node_modules. Delete the `.cjs` file when done
— it's untracked but clutters `git status`.

**Gotcha:** `page.mouse.click(x, y)` / `page.mouse.move` use raw viewport coordinates and do
**not** auto-scroll — if the target element is below the fold (viewport 900px tall, common on
this settings page), the click silently lands outside the page content and nothing happens, with
no error. Call `locator.scrollIntoViewIfNeeded()` before computing `boundingBox()` for manual
mouse actions, or just use `locator.click()` (auto-scrolls).

## Worth driving

- `/pengaturan` (admin settings) — GPS location picker (`resources/js/pengaturan-lokasi.js`,
  Leaflet map), weekly-holiday checkboxes, time simulation panel.
- `/portal/absen` — student self-service face-scan kiosk; camera requires a secure context
  (`localhost` or HTTPS) — the plain-HTTP `.test` Laragon vhost won't grant camera permission.
