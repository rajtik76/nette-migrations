<?php declare(strict_types=1);

namespace Rajtik\Migrations\Tests\fixtures\Seeders;

use Rajtik\Migrations\Seeding\Seeder;
use Rajtik\Migrations\Tests\fixtures\Factories\UserFactory;


final class DatabaseSeeder extends Seeder
{
	public function __construct(
		private readonly UserFactory $users,
	) {
	}


	public function run(): void
	{
		$this->users->create(['name' => 'Admin', 'email' => 'admin@example.com', 'admin' => true]);
		$this->call(PostSeeder::class);
	}
}
