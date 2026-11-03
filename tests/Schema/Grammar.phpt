<?php declare(strict_types=1);

use Nette\Database\Connection;
use Rajtik\Migrations\Schema\Blueprint;
use Rajtik\Migrations\Schema\Grammar\Grammar;
use Rajtik\Migrations\Schema\Grammar\GrammarFactory;
use Rajtik\Migrations\Schema\Grammar\MySqlGrammar;
use Rajtik\Migrations\Schema\Grammar\PostgresGrammar;
use Rajtik\Migrations\Schema\Grammar\SqliteGrammar;
use Rajtik\Migrations\Schema\UnsupportedOperation;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function postsTable(): Blueprint
{
	$table = new Blueprint('posts', creating: true);
	$table->id();
	$table->foreignId('user_id')->constrained()->cascadeOnDelete();
	$table->string('title', 100)->unique();
	$table->text('body')->nullable();
	$table->boolean('published')->default(false);
	$table->integer('views')->default(0)->index();
	$table->string('status')->default("it's");
	$table->timestamp('seen_at')->useCurrent();
	$table->timestamps();
	return $table;
}


/** @return list<string> */
function compile(Grammar $grammar, Blueprint $blueprint): array
{
	return $grammar->compile($blueprint);
}


test('sqlite create', function () {
	Assert::same([
		<<<'SQL'
			create table "posts" (
				"id" integer primary key autoincrement not null,
				"user_id" integer not null,
				"title" varchar not null,
				"body" text null,
				"published" tinyint(1) not null default 0,
				"views" integer not null default 0,
				"status" varchar not null default 'it''s',
				"seen_at" DATETIME not null default (cast(strftime('%s', 'now') as integer)),
				"created_at" DATETIME null,
				"updated_at" DATETIME null,
				constraint "posts_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete cascade
			)
			SQL,
		'create unique index "posts_title_unique" on "posts" ("title")',
		'create index "posts_views_index" on "posts" ("views")',
	], compile(new SqliteGrammar, postsTable()));
});


test('sqlite current timestamp follows the driver datetime format', function () {
	$table = new Blueprint('visits', creating: true);
	$table->timestamp('seen_at')->useCurrent();

	Assert::contains("default (cast(strftime('%s', 'now') as integer))", compile(new SqliteGrammar, $table)[0]);
	Assert::contains('default CURRENT_TIMESTAMP', compile(new SqliteGrammar(unixTimestamps: false), $table)[0]);
});


test('grammar factory detects the sqlite datetime format', function () {
	$table = new Blueprint('visits', creating: true);
	$table->timestamp('seen_at')->useCurrent();
	$sql = fn(array $options): string => compile(GrammarFactory::forConnection(new Connection('sqlite::memory:', options: $options)), $table)[0];

	Assert::contains('strftime', $sql([]));
	Assert::contains('CURRENT_TIMESTAMP', $sql(['formatDateTime' => "'Y-m-d H:i:s'"]));
	Assert::contains('CURRENT_TIMESTAMP', $sql(['formatDateTime' => "'G'"]));
});


test('sqlite stores a date default as a unix timestamp', function () {
	$table = new Blueprint('posts', creating: true);
	$table->timestamp('published_at')->default('2026-01-01 00:00:00');
	$table->timestamp('archived_at')->default(0);
	$table->string('note')->default('2026-01-01 00:00:00');

	$sql = compile(new SqliteGrammar, $table)[0];
	Assert::contains('"published_at" DATETIME not null default ' . (new DateTimeImmutable('2026-01-01 00:00:00'))->getTimestamp(), $sql);
	Assert::contains('"archived_at" DATETIME not null default 0', $sql);
	Assert::contains("\"note\" varchar not null default '2026-01-01 00:00:00'", $sql);
	Assert::contains("default '2026-01-01 00:00:00'", compile(new SqliteGrammar(unixTimestamps: false), $table)[0]);

	$table = new Blueprint('posts', creating: true);
	$table->timestamp('published_at')->default('soon-ish');
	Assert::exception(fn() => compile(new SqliteGrammar, $table), UnsupportedOperation::class, "%a%'published_at'%a%");
});


test('sqlite rejects a current timestamp column on an existing table', function () {
	$table = new Blueprint('posts', creating: false);
	$table->timestamp('seen_at')->useCurrent();

	Assert::exception(fn() => compile(new SqliteGrammar, $table), UnsupportedOperation::class, "%a%'seen_at'%a%");
	Assert::contains('default CURRENT_TIMESTAMP', compile(new MySqlGrammar, $table)[0]);
});


test('mysql create', function () {
	Assert::same([
		<<<'SQL'
			create table `posts` (
				`id` bigint unsigned not null auto_increment primary key,
				`user_id` bigint unsigned not null,
				`title` varchar(100) not null,
				`body` text null,
				`published` tinyint(1) not null default 0,
				`views` int not null default 0,
				`status` varchar(255) not null default 'it''s',
				`seen_at` timestamp not null default CURRENT_TIMESTAMP,
				`created_at` timestamp null,
				`updated_at` timestamp null,
				constraint `posts_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade
			) default character set utf8mb4 collate utf8mb4_unicode_ci engine = InnoDB
			SQL,
		'create unique index `posts_title_unique` on `posts` (`title`)',
		'create index `posts_views_index` on `posts` (`views`)',
	], compile(new MySqlGrammar, postsTable()));
});


test('postgres create', function () {
	Assert::same([
		<<<'SQL'
			create table "posts" (
				"id" bigint generated by default as identity primary key,
				"user_id" bigint not null,
				"title" varchar(100) not null,
				"body" text null,
				"published" boolean not null default false,
				"views" integer not null default 0,
				"status" varchar(255) not null default 'it''s',
				"seen_at" timestamp(0) without time zone not null default CURRENT_TIMESTAMP,
				"created_at" timestamp(0) without time zone null,
				"updated_at" timestamp(0) without time zone null,
				constraint "posts_user_id_foreign" foreign key ("user_id") references "users" ("id") on delete cascade
			)
			SQL,
		'create unique index "posts_title_unique" on "posts" ("title")',
		'create index "posts_views_index" on "posts" ("views")',
	], compile(new PostgresGrammar, postsTable()));
});


test('composite primary key', function () {
	$table = new Blueprint('tokens', creating: true);
	$table->string('email');
	$table->string('token');
	$table->primary('email', 'token');

	Assert::contains('primary key ("email", "token")', compile(new SqliteGrammar, $table)[0]);
});


test('mysql escapes backslash in defaults', function () {
	$table = new Blueprint('t', creating: true);
	$table->string('path')->default('C:\temp');

	Assert::contains("default 'C:\\\\temp'", compile(new MySqlGrammar, $table)[0]);
});


test('alter with foreign key', function () {
	$alter = function (): Blueprint {
		$table = new Blueprint('posts', creating: false);
		$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
		$table->dropColumn('legacy');
		$table->dropIndex('posts_views_index');
		return $table;
	};

	Assert::same([
		'drop index "posts_views_index"',
		'alter table "posts" drop column "legacy"',
		'alter table "posts" add column "category_id" integer null references "categories" ("id") on delete set null',
	], compile(new SqliteGrammar, $alter()));

	Assert::same([
		'drop index `posts_views_index` on `posts`',
		'alter table `posts` drop column `legacy`',
		'alter table `posts` add column `category_id` bigint unsigned null',
		'alter table `posts` add constraint `posts_category_id_foreign` foreign key (`category_id`) references `categories` (`id`) on delete set null',
	], compile(new MySqlGrammar, $alter()));

	Assert::same([
		'drop index "posts_views_index"',
		'alter table "posts" drop column "legacy"',
		'alter table "posts" add column "category_id" bigint null',
		'alter table "posts" add constraint "posts_category_id_foreign" foreign key ("category_id") references "categories" ("id") on delete set null',
	], compile(new PostgresGrammar, $alter()));
});


test('sqlite rejects constraint changes on existing columns', function () {
	$table = new Blueprint('posts', creating: false);
	$table->foreign('user_id')->references('id')->on('users');
	Assert::exception(fn() => compile(new SqliteGrammar, $table), UnsupportedOperation::class, "%a%'posts_user_id_foreign'%a%");

	$table = new Blueprint('posts', creating: false);
	$table->dropForeign('posts_user_id_foreign');
	Assert::exception(fn() => compile(new SqliteGrammar, $table), UnsupportedOperation::class);
});


test('drop foreign', function () {
	$table = new Blueprint('posts', creating: false);
	$table->dropForeign('posts_user_id_foreign');

	Assert::same(['alter table `posts` drop foreign key `posts_user_id_foreign`'], compile(new MySqlGrammar, $table));
	Assert::same(['alter table "posts" drop constraint "posts_user_id_foreign"'], compile(new PostgresGrammar, $table));
});


test('drop all tables', function () {
	Assert::same(['drop table "a", "b" cascade'], (new PostgresGrammar)->compileDropAllTables(['a', 'b']));
	Assert::same([], (new PostgresGrammar)->compileDropAllTables([]));
	Assert::same(['set foreign_key_checks = 0', 'drop table `a`', 'set foreign_key_checks = 1'], (new MySqlGrammar)->compileDropAllTables(['a']));
});
