# Demo Persian Storefront (PHP + Tailwind)

A simple Persian-language storefront demo with:

- Simple register/login (no SMS verification)
- Product selection with fixed pricing tiers
- Order creation and redirect to ZarinPay gateway endpoint
- Payment callback verifier endpoint compatible with provided base code style

## Run locally

```bash
php -S 0.0.0.0:8080 -t public

sudo chmod -R 775 /var/www/miladrajabi.com/jet/data
sudo chown -R www-data:www-data /var/www/miladrajabi.com/jet/data
```

Open: `http://localhost:8080`

## Deploy on host

1. Upload the whole project.
2. **Recommended:** Point web root to `public/`.
3. If your host cannot change web root (or it points to project root), keep the provided root `.htaccess` and `index.php` fallback files so requests are forwarded to `public/`.
4. Edit `config/config.php` with your domain and token.
5. Ensure `storage/` is writable by PHP.

## Important

This project is provided as a **demo storefront**. Use it only for lawful and transparent business purposes.
