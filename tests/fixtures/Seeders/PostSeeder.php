<?php declare(strict_types=1);

namespace Rajtik\Migrations\Tests\fixtures\Seeders;

use Nette\Database\Explorer;
use Rajtik\Migrations\Seeding\Seeder;
use Rajtik\Migrations\Tests\fixtures\Factories\PostFactory;


final class PostSeeder extends Seeder
{
	public function __construct(
		private readonly Explorer $database,
		private readonly PostFactory $posts,
	) {
	}


	public function run(): void
	{
		$adminId = $this->database->table('users')->where('email', 'admin@example.com')->fetchField('id');
		$this->posts->createMany(2, ['user_id' => $adminId]);
	}
}
