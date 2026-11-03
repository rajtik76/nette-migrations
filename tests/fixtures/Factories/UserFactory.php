<?php declare(strict_types=1);

namespace Rajtik\Migrations\Tests\fixtures\Factories;

use Rajtik\Migrations\Factory\Factory;


final class UserFactory extends Factory
{
	protected function table(): string
	{
		return 'users';
	}


	protected function definition(): array
	{
		return [
			'name' => $this->faker()->name(),
			'email' => $this->faker()->unique()->safeEmail(),
		];
	}
}
