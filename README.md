# Employee Leave System

Laravel app for leave requests, approvals, and admin management.

## 1. Install tools on Zorin Linux

Zorin is Ubuntu-based. PHP 8.3 or newer is required.

```bash
sudo apt update
php -v | head -1
```

If PHP is below 8.3:

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

Install the rest:

```bash
sudo apt install -y php8.3 php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
  composer mysql-server nodejs npm git unzip

sudo systemctl enable --now mysql
```

If your system already has PHP 8.3, install the matching `php-*` packages without the `8.3` suffix.

## 2. Create the database

```bash
sudo mysql
```

In the MySQL prompt:

```sql
CREATE DATABASE leave_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'leave_user'@'localhost' IDENTIFIED BY 'use-a-strong-password';
GRANT ALL PRIVILEGES ON leave_system.* TO 'leave_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Configure the app

```bash
cd /path/to/leave-system
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000
DB_HOST=127.0.0.1
DB_DATABASE=leave_system
DB_USERNAME=leave_user
DB_PASSWORD=use-a-strong-password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

`.env.example` sets `APP_DEBUG=true`. Change it to `false`.

## 4. Migrate, seed, and create admin

```bash
php artisan migrate --seed
php artisan app:create-admin
```

`migrate --seed` creates roles and leave types. It does not create employees. The create-admin command asks for name, email, and password.

This is for first-time setup only. For a normal restart, run `php artisan serve`; data stays in MySQL. `php artisan migrate:fresh` is destructive and only for a local reset.

## 5. Front-end assets

```bash
npm install
npm run build
```

The current pages load `public/css/app.css` and `public/js/app.js` directly. `npm run build` is only needed if you edit files under `resources/css` or `resources/js` and switch those pages to `@vite`.

## 6. Run

```bash
php artisan serve
```

Open `http://127.0.0.1:8000/login`.

An admin creates employees at `/users`. The temporary password is shown once. The employee must change it at first login.

Optional test run:

```bash
php artisan test
```
