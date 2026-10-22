# Zuri Beauty Lounge

Salon website, booking system and admin panel. Plain PHP 8.2 + MySQL/MariaDB, no framework.

## Layout

```
public/        the only folder a browser can reach (index.php front controller, css, js)
src/           application code (Model, Http, Controller, Sms, Support, views)
migrations/    ordered .sql files, run by bin/migrate.php
bin/           command-line tools
storage/       logs and the generated app key (never served)
legacy/        the old one-file site, kept for reference and blocked from the web
```

## First time setup

```
copy .env.example .env        then fill in DB_USER, DB_PASS, DB_NAME
php bin/migrate.php up --create-database
php bin/admin.php create yourname --first=Your --role=owner
```

`.env` and `storage/` are gitignored. If `APP_KEY` is left blank the app generates
`storage/.appkey` itself; set `APP_KEY` to a 32+ character secret on a real server.

## Everyday commands

| Command | What it does |
| --- | --- |
| `php bin/migrate.php status` | which migrations are applied, which are pending |
| `php bin/migrate.php up` | apply pending migrations |
| `php bin/migrate.php rollback --steps=1` | undo the last migration (uses its `-- @down` section) |
| `php bin/migrate.php fresh` | drop every table and rebuild from scratch |
| `php bin/admin.php list` | show admin accounts, roles, last sign-in |
| `php bin/admin.php create <user> --role=owner|manager|staff` | add an account (asks for the password) |
| `php bin/admin.php password <user>` | reset a password |
| `php bin/admin.php activate <user>` | re-enable a disabled account |
| `php -S 127.0.0.1:8080 -t public bin/serve.php` | run the site locally without Apache |

Add a migration as `migrations/YYYYMMDD_HHMM_short_name.sql`. Statements are split on
semicolons outside quotes and comments, so keep each file to plain SQL.

## Booking SMS

Switch it on under **SMS** in the admin panel. Endpoint, method, sender ID and the
provider parameter names are all editable there, so swapping providers is a settings
change, not a code change. Defaults match `sms.textsms.co.ke`:

```
GET  https://sms.textsms.co.ke/api/services/sendsms/?apikey=…&partnerID=…&message=…&shortcode=…&mobile=…
```

Credentials come from `.env` (`SMS_KEY`, `SMS_PARTNER_ID`, `SMS_SHORTCODE`) or from the
panel, where they are stored in the database and shown masked. Recipients are the salon
number in settings plus any admin who ticked "text me" on the Team page.

Every send is written to `sms_queue` (`queued`, `sent`, `failed`, `skipped`) and a failed
message can be retried from the panel, up to 4 attempts. A booking is always saved first:
if the gateway is down or unconfigured the message is logged as skipped and the booking
still goes through. The SMS page has a test-send box and a live preview of the message.

## Admin themes

Each admin picks their own colours under **Appearance**: five presets (the default is the
house plum/rose palette the site shipped with) or five custom pickers with a live preview.
Derived shades, borders and button text are calculated from those five, so any palette
stays readable. Only the person who picks it sees it.

## Security notes

- Secrets live in `.env` and the database, never in the code or in URLs.
- Every SQL statement uses prepared parameters.
- Admin forms use a session CSRF token; the public booking form uses a signed,
  expiring single-purpose token so visitors need no cookie.
- Content-Security-Policy with a per-request nonce, `X-Frame-Options: DENY`,
  nosniff, referrer policy, HSTS once on HTTPS.
- Sign-ins are throttled in the database (8 failures per 15 minutes per username and IP)
  and recorded in the activity log. Sessions expire after 30 idle minutes.
- Booking slots are locked during insert, so two people cannot take the last slot.
- Honeypot plus per-IP and per-number rate limits on the public form.
