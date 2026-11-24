<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## GeoNexa mobile API

The mobile API is available under `/api/v1` and returns JSON. Use `Authorization: Bearer <access_token>` on protected requests.

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/v1/auth/register` | Register with `name`, `email`, `pin`, and `device_id` |
| POST | `/api/v1/auth/check-email` | Check whether an email account exists before login/registration |
| POST | `/api/v1/auth/login` | Login with `identifier` (email/phone), `pin`, and `device_id` |
| GET | `/api/v1/me` | Get the current user |
| GET | `/api/v1/auth/check` | Validate the current token |
| GET | `/api/v1/app-settings` | Read app control flags and maintenance status |
| POST | `/api/v1/auth/refresh` | Rotate the refresh token and issue a new access token |
| POST | `/api/v1/auth/logout` | Revoke the current token |
| GET | `/api/v1/subscription/status` | View subscription/trial status and remaining days |
| POST | `/api/v1/subscription/start-trial` | Start the one-time 14-day trial |
| POST | `/api/v1/subscription/payment` | Submit bKash payment for a paid package |
| GET | `/api/v1/subscription/payments` | View your payment request history |
| GET | `/api/v1/divisions` | Get divisions for PAID/TRIAL accounts |
| GET | `/api/v1/districts?division_id=3` | Get districts under a division |
| GET | `/api/v1/upazilas?district_id=25` | Get upazilas/thanas under a district |
| GET | `/api/v1/profile` | View the authenticated profile |
| PUT/PATCH/POST | `/api/v1/profile` | Update name, phone, or profile photo; email cannot change. For multipart photo uploads use POST with `_method=PUT` |
| GET | `/api/v1/profile/photo` | View the authenticated private profile photo |
| GET | `/api/v1/divisions/{id}/map` | Download a division map image with Bearer token |
| GET | `/api/v1/districts/{id}/map` | Download a district map image with Bearer token |
| GET | `/api/v1/upazilas/{id}/map` | Download an upazila map image with Bearer token |

Names must be at least 3 characters, emails use RFC validation and must be unique, and Bangladesh phone numbers must be 11 digits starting with `013` through `019` (for example `01712345678`) and must be unique. Registration returns field-level validation errors when an email or phone is already registered. PINs must be exactly 6 digits. Login requests must include the current `fcm_token`; it is updated on `app_users` and recorded in the login activity log. A new login replaces the user's current access and refresh tokens, so tokens from another device immediately receive `401`. Store both tokens returned by login/register and call `/auth/refresh` before the access token expires; replace both stored values after every successful refresh because refresh tokens rotate. Set a separate random `API_JWT_SECRET` in production; access and refresh lifetimes are controlled by `API_ACCESS_TTL` and `API_REFRESH_TTL` in minutes.
Only users with `status: "active"` can login. Inactive or blocked accounts receive HTTP `403` with `code: "ACCOUNT_INACTIVE"`.

Mobile apps should delete their stored token and navigate to the login screen whenever a protected endpoint returns `401` with `code: "TOKEN_REVOKED"` or `code: "UNAUTHENTICATED"`.

`GET /api/v1/app-settings` returns boolean controls such as screen lock, screenshot,
screen recording, short video, server-down, and force-update settings. When server-down
mode is enabled, authenticated app endpoints return `503 SERVER_DOWN` with the configured
maintenance message. App admins can force logout every user from the App Settings page.
The same response includes `bkash_merchant_number`, which the app should show as the
destination number when submitting a bKash package payment.

`GET /api/v1/divisions` requires the same Bearer token and an active PAID/TRIAL package. The current date must be between `package_start_date` and `package_end_date` (inclusive), and `package_status` must be `active`. FREE accounts receive `SUBSCRIPTION_REQUIRED`; expired, pending, cancelled, or out-of-date packages receive `SUBSCRIPTION_EXPIRED`.

Each account can start the 14-day trial only once. The trial starts on the current date,
ends after 14 calendar days (inclusive), and automatically loses location access after the
end date. Use the subscription status endpoint to show `active`, `expired`, `pending`,
`cancelled`, or `none` and `days_remaining`.

Paid package payments are submitted with the customer's bKash number, merchant/personal
bKash number, transaction ID, amount, package name, and duration. New requests remain
`pending` until an admin verifies the transaction. Only approved requests activate the
PAID package.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
