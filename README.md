<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Local CMS Development

### First-time setup (fresh clone or new machine)

```bash
cd CMS-BACK
cp .env.example .env     # set DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
composer dev             # setup + server in one command
```

### Normal day

```bash
cd CMS-BACK
composer dev
```

Backend is ready at `http://127.0.0.1:8001/api/v1`.

Frontend (separate terminal, from `CMS-FRONT/`):

```bash
npm run dev -- --port 5174
```

### After a database reset (`migrate:fresh`)

```bash
php artisan migrate:fresh
composer dev
```

`composer dev` re-runs setup automatically — keys are reused, client and super admin are recreated.

### Available commands

| Command | What it does |
|---|---|
| `composer dev` | Setup + start server (one command for normal use) |
| `composer local:setup` | Setup only (migrations, keys, client, admin) — no server |
| `php artisan cms:dev` | Same as `composer dev` |
| `php artisan cms:local-setup` | Same as `composer local:setup` |

### What `cms:local-setup` does

| Step | Behaviour |
|---|---|
| Environment guard | Refuses to run outside `local` / `testing` |
| Migrations | Runs `migrate` (no-op if up to date) |
| Passport keys | Generates only if `storage/oauth-*.key` are missing; **never forces** |
| Key permissions | Sets both keys to `chmod 600` |
| Passport client | Idempotent — creates one personal-access client if none exists |
| Super admin | `updateOrCreate` — never duplicates the account |
| Output | Prints masked credentials then hands off to `serve` |

### Super admin login

| Field    | Value |
|---|---|
| Email    | `arsany.ayman02@gmail.com` |
| Password | *(see `database/seeders/SuperAdminSeeder.php`)* |
| Role     | `super_admin` |

### Passport key management

> **Never run `php artisan passport:keys --force` as part of normal development.**
> That flag regenerates the RSA key pair, which **immediately invalidates every
> existing token** for all users and clients. Only run it intentionally when
> rotating keys (e.g. a key has been compromised).

Keys live on the filesystem (`storage/oauth-*.key`) and are gitignored.
Passport clients live in the database (`oauth_clients` table).
They are independent: resetting the database removes clients but leaves keys intact.

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
