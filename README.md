# Demo Persian Storefront (PHP + Tailwind)

A simple Persian-language storefront demo with:

- Simple register/login (no SMS verification)
- Product selection with fixed pricing tiers
- Order creation and redirect to ZarinPay gateway endpoint
- Payment callback verifier endpoint compatible with provided base code style

## Run locally

```bash
php -S 0.0.0.0:8080 -t public
```

Open: `http://localhost:8080`

## Deploy on host

1. Upload the whole project.
2. Point web root to `public/`.
3. Edit `config/config.php` with your domain and token.
4. Ensure `storage/` is writable by PHP.

## Important

This project is provided as a **demo storefront**. Use it only for lawful and transparent business purposes.
