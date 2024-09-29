# Laravel After Seeders

<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders-seedersders/license.svg" alt="License"></a>

![Laravel After Seeders](https://cdn.davidvandertuijn.nl/github/laravel-after-seeders.png)

This library adds seeders with ***versioning*** support for [Laravel](https://laravel.com/), suitable for a ***production environment***.
The seeders are stored in the *database/after_seeders* directory using JSON format.
Progress is tracked in the after_seeders table. so that the seeder is only run once.

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/davidvandertuijn)

## Install

```
composer require davidvandertuijn/laravel-after-seeders
```

Run migration

```php
php artisan migrate
```

Publish config

```
php artisan vendor:publish --provider="Davidvandertuijn\LaravelAfterSeeders\ServiceProvider"
```

## Usage

### Create a new seeder and fill it manually.

With the command below you create an empty after seeder, good if you are already familiar with the JSON structure, and you want to add the data manually.

```php
php artisan after-seeders:make my_table
```

Created Seeder: */database/after_seeders/YYYY_MM_DD_XXXXXX_my_table.json*:

```json
{
    "RECORDS": [
        {
            "name": "Example"
        }
    ]
}
```

In [Navicat for MySQL](https://www.navicat.com/en/products/navicat-for-mysql), the same structure is used when exporting to JSON file (.json).

### Create a new seeder and fill it automaticly.

With the command below you create a fully completed seeder based on existing records in a table.

```php
php artisan after-seeders:generate my_table
```

Which columns are requested depends on the specified table. Columns that are not answered are not included in the seeder. At range you specify which record IDs should be included in de seeder.

```bash
Columns
Add column "id" ? (yes/no) [no]: y
Add column "name" ? (yes/no) [no]: y
Add column "dateofbirth" ? (yes/no) [no]:
Range
my_table.id from [0]: 12
my_table.id to [14]: 14
```

Created Seeder: */database/after_seeders/YYYY_MM_DD_XXXXXX_my_table.json*:

```json
{
    "RECORDS": [
        {
            "id": 12,
            "name": "Bill Gates"
        },
        {
            "id": 13,
            "name": "Steve Jobs"
        },
        {
            "id": 14,
            "name": "John Doe"
        }
    ]
}
```

### Running Seeders

The pending after seeders are executed with the command below:

```php
php artisan after-seeders:run
```

During execution it is checked whether the table and / or columns actually exist, otherwise the seeder is skipped.

```bash
Check seeder: YYYY_MM_DD_XXXXXX_my_table
[OK] Table "my_table" exists.
[OK] Columns "id, name" exists.
```
***Created At***

If the table has a ***created_at*** column, but this is missing from the seeder, the current time is filled in.

***Update Or Insert***

If the ***id*** column exists in the seeder, the [updateOrInsert](https://laravel.com/docs/8.x/queries#update-or-insert) method is used, otherwise the [insert](https://laravel.com/docs/8.x/queries#inserts) method.
