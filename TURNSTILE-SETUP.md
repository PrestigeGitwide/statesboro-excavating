Cloudflare Turnstile Setup

1. Add your site key in:
   `index.html`
   Find:
   `data-sitekey="PASTE_YOUR_CLOUDFLARE_TURNSTILE_SITE_KEY_HERE"`

2. Add your private keys in:
   `config.php`
   Replace:
   - `PASTE_YOUR_TURNSTILE_SECRET_KEY_HERE`
   - `PASTE_YOUR_GOHIGHLEVEL_WEBHOOK_URL_HERE`

3. Do not put your secret key in any browser file.
   Do not add it to:
   - `index.html`
   - `script.js`
   - `styles.css`

4. Files now included:
   - `submit-estimate.php`
   - `config.php`

5. `submit-estimate.php` now:
   - validates required form fields
   - verifies the Turnstile token with Cloudflare
   - sends the lead to GoHighLevel
   - returns a JSON success/error response
