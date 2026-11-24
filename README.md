# silverstripe-service-desk

A small customer service desk for apps built on `silverstripe-restful-framework`: tickets, contact and feedback endpoints, a CMS admin to answer them, and a public portal.

## Install

```sh
composer require bradtipper/silverstripe-service-desk
```

Run `dev/build?flush=1` afterwards.

## What it adds

### API

- `GET/POST /api/v1/tickets`, `GET /api/v1/tickets/{id}`, `POST /api/v1/tickets/{id}/messages` (add a note) and `/status` (close or reopen): a signed-in user's own requests.
- `POST /api/v1/contact`: contact form for visitors (Turnstile checked; skipped for signed-in users).
- `POST /api/v1/feedback`: quick feedback from signed-in users.

### CMS

- Tickets: status (open, in progress, planned, resolved, closed), priority, and internal or public notes. A public reply emails the user, and so does a status change.
- Support: contact messages and feedback.
- Each user shows their feedback, contact messages and (in the account data export) their tickets.

### Public website

- `/portal`: a branded page where customers sign in with their app email and password to see their tickets, reply, close or reopen, and open new ones. `/portal?view=contact` and `/portal?view=feedback` show the forms for visitors.
- Embed anywhere:

```html
<div data-support-portal></div>
<div data-contact-form></div>
<div data-feedback-form></div>
<script src="/portal/widget.js" defer></script>
```

The widgets call the same `/api/v1` endpoints as the apps and keep tokens in `sessionStorage`, so the pages stay cacheable (no cookies or sessions).

## Configure

Branding and settings come from the framework's `AppConfig`, in your project's YAML:

```yaml
BradTipper\AppBackend\AppConfig:
  name: MyApp                                   # portal header, emails
  brand_color: '#2f4c63'                        # portal header and buttons
  brand_color_text: '#ffffff'
  brand_logo_url: 'https://myapp.example/brand/apple-touch-icon.png'
  privacy_url: 'https://myapp.example/privacy'  # linked from the contact and feedback consent line
  turnstile_site_key: '0x4AAAA...'              # Cloudflare Turnstile (public key) for the portal forms
  tickets_path: '/support'                      # web app screen; the ticket id is appended in emails
  support_email: 'support@myapp.example'        # new tickets are emailed here, and replies go here
  turnstile_secret_key: '...'                   # live fragment only: verifies the captcha on contact
```

A SiteConfig `TurnstileSiteKey` field, if your site defines one, overrides `turnstile_site_key`. Without a key the captcha is skipped, which the API only allows outside live.
Models and admins can be extended in the usual Silverstripe way. Other modules can add their own models to the Support admin through `SupportAdmin.managed_models`.

## Licence

Proprietary.
