# Laravel After Seeders

<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-after-seeders"><img src="https://poser.pugx.org/davidvandertuijn/laravel-after-seeders-seedersders/license.svg" alt="License"></a>

![Laravel After Seeders](https://cdn.davidvandertuijn.nl/github/laravel-after-seeders.png)

This library adds seeder functionality with ***versioning*** support for [Laravel](https://laravel.com/), making it ideal for a ***production environment***. Seeders are stored in the *database/after_seeders* directory in JSON format. The execution progress of each seeder is tracked in the after_seeders table, ensuring that each seeder is only run once.

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/davidvandertuijn)

## Install

Install the package via Composer:

```shell
composer require davidvandertuijn/laravel-after-seeders
```

Run the migrations:

```shell
php artisan migrate
```

Publish the configuration file:

```shell
php artisan vendor:publish --provider="Davidvandertuijn\LaravelAfterSeeders\ServiceProvider"
```

## Usage

### Create a New Seeder Automatically

Use the command below to create a complete seeder based on existing records in a database table.

```shell
php artisan after-seeders:generate my_table
```

The command will prompt you to include or exclude specific columns from the table. If you skip a column, it won’t be included in the seeder file. You can also define a range of record IDs to include.

Example prompt:

```
Columns for table "my_table".
Would you like to add the column "id" ? (yes/no) [no]: y
Would you like to add the column "name" ? (yes/no) [no]: y
Would you like to add the column "dateofbirth" ? (yes/no) [no]:
Select range for table "my_table".
Enter the starting ID [0]: 12
Enter the ending ID [13]: 13
```

After completion, you’ll get the following message:

```
database/after_seeders/YYYY_MM_DD_XXXXXX_my_table.json ... SUCCESS
```

And the seeder will look like this:

```json
{
    "RECORDS": [
        {
            "id": 12,
            "name": "John Doe"
        },
        {
            "id": 13,
            "name": "Jane Doe"
        }
    ]
}
```

### Create a New Seeder Manually

Use the following command to generate an empty “after seeder” file. This option is ideal if you’re already familiar with the JSON structure and prefer to manually input the data.

```shell
php artisan after-seeders:make my_table
```

You’ll see the following output upon success:

```
database/after_seeders/YYYY_MM_DD_XXXXXX_my_table.json ... SUCCESS
```

A basic structure for the JSON file will look like this:

```json
{
    "RECORDS": [
        {
            "name": "Example"
        }
    ]
}
```

If you use [Navicat for MySQL](https://www.navicat.com/en/products/navicat-for-mysql), it follows the same format when exporting data to a .json file.

### Running Seeders

To execute pending “after seeders,” run the following command:

```shell
php artisan after-seeders:run
```

The command checks whether the specified table and columns exist. If not, the seeder will be skipped.

After completion, you’ll get the following message:

```
Run batch "X".
database/after_seeders/YYYY_MM_DD_XXXXXX_my_table.json ... SUCCESS
```

---

***Handling created_at***

If the table has a ***created_at*** column and it’s missing from the seeder, the current timestamp will be automatically inserted.

***Update Or Insert***

If the seeder contains an ***id*** column, the [updateOrInsert](https://laravel.com/docs/10.x/queries#update-or-insert) method will be used. Otherwise, the [insert](https://laravel.com/docs/10.x/queries#insert-statements) method will be applied.
