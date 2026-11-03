Nette Migrations
================

Laravel-like database migrations, schema builder, seeders and factories for
[Nette Database](https://doc.nette.org/en/database). No third-party migration library.

- SQLite, MySQL, MariaDB and PostgreSQL from one migration file
- batches with rollback, reset, fresh and status
- seeders resolved from the DI container, factories with [Faker](https://fakerphp.org)
- `make:migration`, `make:seeder`, `make:factory` generators
- SQLite foreign keys enabled on every connection

Commands run through [rajtik76/nette-console](https://github.com/rajtik76/nette-console)
(`php nette`); the extension registers itself via package discovery.


Installation
------------

    composer require rajtik76/nette-migrations
    composer require --dev fakerphp/faker

Map the database namespaces in `composer.json`:

```json
"autoload": {
    "psr-4": {
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

Register factories as services, so seeders can get them injected:

```neon
search:
    - in: %rootDir%/database/factories
      classes:
        - *Factory
```


Commands
--------

| Command | Description |
|---|---|
| `migrate [--seed]` | Run pending migrations as a new batch |
| `migrate:rollback [--step=N]` | Roll back the last batch, or the last N migrations |
| `migrate:reset` | Roll back all migrations |
| `migrate:fresh [--seed]` | Drop all tables and run all migrations |
| `migrate:status` | List migrations with their batch |
| `db:seed [--class=UserSeeder]` | Run `Database\Seeders\DatabaseSeeder` or the given seeder |
| `make:migration create_posts_table` | `--create=table` / `--table=table`, otherwise inferred from the name |
| `make:seeder User` | `database/seeders/UserSeeder.php` |
| `make:factory Post` | `database/factories/PostFactory.php` for table `posts` (`--table` to override) |

In production mode (`NETTE_DEBUG=0`) destructive commands ask for confirmation; `--force` skips it.


Migrations
----------

```php
<?php declare(strict_types=1);

use Rajtik\Migrations\Migration\Migration;
use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\Schema;


return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->unique();
            $table->text('body')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }


    public function down(Schema $schema): void
    {
        $schema->dropIfExists('posts');
    }
};
```

Columns: `id`, `integer`, `bigInteger`, `foreignId`, `string`, `text`, `boolean`, `timestamp`, `timestamps`.
Modifiers: `nullable`, `default`, `useCurrent`, `primary`, `unique`, `index`.
Table: `primary(...)`, `unique(...)`, `index(...)`, `foreign(...)->references()->on()`,
`dropColumn`, `dropIndex`, `dropUnique`, `dropForeign`.
Schema: `create`, `table`, `drop`, `dropIfExists`, `hasTable`, `statement`, `connection`.

Each migration runs in a transaction on SQLite and PostgreSQL. MySQL/MariaDB commit DDL implicitly.
SQLite can add a foreign key only together with a new column and cannot drop one.


Seeders and factories
---------------------

```php
final class UserFactory extends Factory
{
    public function __construct(Explorer $database, private readonly Passwords $passwords)
    {
        parent::__construct($database);
    }


    protected function table(): string
    {
        return 'users';
    }


    protected function definition(): array
    {
        return [
            'name' => $this->faker()->name(),
            'email' => $this->faker()->unique()->safeEmail(),
            'password' => $this->passwords->hash('password'),
        ];
    }
}


final class DatabaseSeeder extends Seeder
{
    public function __construct(private readonly UserFactory $users)
    {
    }


    public function run(): void
    {
        $this->users->create(['email' => 'test@example.com']);
        $this->users->state(['name' => 'Admin'])->createMany(3);
        $this->call(PostSeeder::class);
    }
}
```

Factory: `make`, `makeMany`, `create`, `createMany`, `state(array|Closure)`.


Configuration
-------------

```neon
migrations:
    table: migrations
    directory: %rootDir%/database/migrations
    stubs: %rootDir%/stubs              # overrides migration*.stub, seeder.stub, factory.stub
    sqliteForeignKeys: true
    seeders:
        namespace: Database\Seeders
        directory: %rootDir%/database/seeders
        default: DatabaseSeeder
    factories:
        namespace: Database\Factories
        directory: %rootDir%/database/factories
```


Checks
------

    composer run ci:check    # PHPStan level 8 + Nette Tester

Integration tests use SQLite and, when configured, other databases (`dsn|user|password`):

    MIGRATIONS_TEST_MYSQL='mysql:host=127.0.0.1;port=33306;dbname=migrations_test|root|secret' \
    MIGRATIONS_TEST_MARIADB='mysql:host=127.0.0.1;port=33307;dbname=migrations_test|root|secret' \
    MIGRATIONS_TEST_PGSQL='pgsql:host=127.0.0.1;port=54329;dbname=migrations_test|postgres|secret' \
    composer run tester
