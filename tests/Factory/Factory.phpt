<?php declare(strict_types=1);

use Rajtik\Migrations\Tests\fixtures\Factories\PostFactory;
use Rajtik\Migrations\Tests\fixtures\Factories\UserFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


foreach (testConnections() as $driver => $connection) {
	test("$driver: create rows", function () use ($connection) {
		createMigrator($connection, __DIR__ . '/../fixtures/migrations')->run();
		$explorer = createExplorer($connection);
		$users = new UserFactory($explorer);

		$jane = $users->create(['name' => 'Jane', 'admin' => true]);
		Assert::same('Jane', $jane['name']);
		Assert::true((bool) $jane['admin']);
		Assert::type('int', $jane['id']);

		Assert::count(3, $users->createMany(3));
		Assert::same([], $users->createMany(0));
		Assert::same(4, $explorer->table('users')->count('*'));

		$post = (new PostFactory($explorer))->create(['user_id' => $jane['id']]);
		Assert::same($jane['id'], $post['user_id']);
	});
}


test('states are applied in order and do not leak', function () {
	$users = new UserFactory(createExplorer(testConnections()['sqlite']));
	$admin = $users
		->state(['admin' => true, 'name' => 'jane'])
		->state(fn(array $attributes): array => ['name' => ucfirst((string) $attributes['name'])]);

	Assert::equal(['admin' => true, 'name' => 'Jane'], array_intersect_key($admin->make(), ['admin' => 1, 'name' => 1]));
	Assert::same('Override', $admin->make(['name' => 'Override'])['name']);
	Assert::false(array_key_exists('admin', $users->make()));
	Assert::count(2, $users->makeMany(2));
	Assert::same([], $users->makeMany(0));
});
