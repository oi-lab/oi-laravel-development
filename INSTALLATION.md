# Installation Guide

## Quick Start

### 1. Add the Package

Add this to your main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/oi-lab/oi-laravel-development"
        }
    ]
}
```

### 2. Require the Package

```bash
composer require oi-lab/oi-laravel-development --dev
```

### 3. Publish Configuration

```bash
php artisan vendor:publish --tag=laravel-development-config
```

This will create `config/oi-laravel-development.php`.

### 4. Configure Your Seeders

Edit `config/oi-laravel-development.php`:

```php
'seeders' => [
    'Database\\Seeders\\UserSeeder' => 'Users',
    'Database\\Seeders\\OrganizationSeeder' => 'Organizations',
    'Database\\Seeders\\ProjectSeeder' => 'Projects',
    // Add your seeders here
],
```

### 5. Set Environment Variables (Optional)

Add to your `.env`:

```env
DEV_DEFAULT_PASSWORD=password
```

## Verify Installation

Run this command to see all available commands:

```bash
php artisan list dev
php artisan list init
```

You should see:
- `dev:clear-log`
- `dev:clear-storage`
- `dev:force-password`
- `dev:reset`
- `init:all`

## First Use

Test the package with:

```bash
# Clear logs
php artisan dev:clear-log

# Reset development environment
php artisan dev:reset --force
```

## Troubleshooting

### Commands not showing up

1. Clear Laravel's cache:
```bash
php artisan config:clear
php artisan cache:clear
```

2. Dump autoload:
```bash
composer dump-autoload
```

### Config file not found

Make sure you published the config:
```bash
php artisan vendor:publish --tag=laravel-development-config
```

Check that `config/oi-laravel-development.php` exists.

### User model not found in ForcePassword command

The `dev:force-password` command uses `App\Models\User`. Make sure your User model exists at this location.

## Uninstallation

```bash
# Remove the package
composer remove oi-lab/oi-laravel-development

# Remove the config file (optional)
rm config/oi-laravel-development.php
```
