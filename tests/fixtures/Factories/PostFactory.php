<?php declare(strict_types=1);

namespace Rajtik\Migrations\Tests\fixtures\Factories;

use Rajtik\Migrations\Factory\Factory;


final class PostFactory extends Factory
{
	protected function table(): string
	{
		return 'posts';
	}


	protected function definition(): array
	{
		return ['title' => $this->faker()->sentence(3)];
	}
}
