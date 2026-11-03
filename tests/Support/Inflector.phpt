<?php declare(strict_types=1);

use Rajtik\Migrations\Support\Inflector;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('snake', function () {
	Assert::same('create_users_table', Inflector::snake('CreateUsersTable'));
	Assert::same('add_bio_to_users_table', Inflector::snake('add bio-to users_table'));
	Assert::same('user2_fa', Inflector::snake('user2Fa'));
});


test('plural', function () {
	Assert::same('users', Inflector::plural('user'));
	Assert::same('categories', Inflector::plural('category'));
	Assert::same('days', Inflector::plural('day'));
	Assert::same('addresses', Inflector::plural('address'));
	Assert::same('boxes', Inflector::plural('box'));
});
