<?php declare(strict_types=1);

use Nette\Database\Connection;
use Nette\Database\ForeignKeyConstraintViolationException;
use Nette\Database\UniqueConstraintViolationException;
use Rajtik\Migrations\Schema\Blueprint;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function insertUser(Connection $connection, string $email): int
{
	$connection->query('INSERT INTO users ?', ['email' => $email]);
	return (int) $connection->fetchField('SELECT id FROM users WHERE email = ?', $email);
}


foreach (testConnections() as $driver => $connection) {
	test("$driver: create tables with defaults and constraints", function () use ($connection) {
		$schema = createSchema($connection);
		$schema->create('users', function (Blueprint $table): void {
			$table->id();
			$table->string('email')->unique();
			$table->boolean('admin')->default(false);
			$table->string('locale', 5)->default('en');
			$table->timestamp('seen_at')->useCurrent();
			$table->timestamps();
		});
		$schema->create('posts', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('title');
			$table->integer('views')->default(0)->index();
		});

		Assert::true($schema->hasTable('users'));
		Assert::true($schema->hasTable('posts'));
		Assert::false($schema->hasTable('comments'));

		$userId = insertUser($connection, 'jane@example.com');
		$user = (array) $connection->fetch('SELECT * FROM users WHERE id = ?', $userId);
		Assert::false((bool) $user['admin']);
		Assert::same('en', $user['locale']);
		Assert::type(DateTimeInterface::class, $user['seen_at']);
		Assert::null($user['created_at']);

		Assert::exception(fn() => insertUser($connection, 'jane@example.com'), UniqueConstraintViolationException::class);
		Assert::exception(
			fn() => $connection->query('INSERT INTO posts ?', ['user_id' => 999, 'title' => 'Orphan']),
			ForeignKeyConstraintViolationException::class,
		);

		$connection->query('INSERT INTO posts ?', ['user_id' => $userId, 'title' => 'Hello']);
		$connection->query('DELETE FROM users WHERE id = ?', $userId);
		Assert::same(0, (int) $connection->fetchField('SELECT COUNT(*) FROM posts'));
	});


	test("$driver: alter table", function () use ($connection) {
		$schema = createSchema($connection);
		$schema->table('users', function (Blueprint $table): void {
			$table->string('nickname')->nullable();
			$table->dropColumn('locale');
		});
		$schema->table('posts', function (Blueprint $table): void {
			$table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
		});

		Assert::contains('nickname', columnNames($connection, 'users'));
		Assert::notContains('locale', columnNames($connection, 'users'));

		$authorId = insertUser($connection, 'author@example.com');
		$editorId = insertUser($connection, 'editor@example.com');
		$connection->query('INSERT INTO posts ?', ['user_id' => $authorId, 'editor_id' => $editorId, 'title' => 'Edited']);
		$connection->query('DELETE FROM users WHERE id = ?', $editorId);

		Assert::null($connection->fetchField('SELECT editor_id FROM posts'));
	});


	test("$driver: drop tables", function () use ($connection) {
		$schema = createSchema($connection);
		$schema->dropIfExists('missing');
		$schema->create('tags', function (Blueprint $table): void {
			$table->string('name');
			$table->primary('name');
		});
		$schema->drop('tags');
		Assert::false($schema->hasTable('tags'));

		$schema->dropAllTables();
		Assert::false($schema->hasTable('users'));
		Assert::false($schema->hasTable('posts'));
	});
}
