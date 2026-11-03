<?php declare(strict_types=1);

namespace Rajtik\Migrations\Seeding;

use Nette\DI\Container;


final readonly class SeederRunner
{
	public function __construct(
		private Container $container,
	) {
	}


	/**
	 * @param class-string $class
	 * @param ?\Closure(class-string<Seeder>, float): void $onSeeded receives class and duration in ms
	 */
	public function run(string $class, ?\Closure $onSeeded = null): void
	{
		$seeder = $this->container->createInstance($class);
		if (!$seeder instanceof Seeder) {
			throw new \InvalidArgumentException("Class '$class' is not a " . Seeder::class . '.');
		}

		$seeder->setCaller(fn(string $nested) => $this->run($nested, $onSeeded));

		$start = hrtime(true);
		$seeder->run();

		if ($onSeeded !== null) {
			$onSeeded($seeder::class, (hrtime(true) - $start) / 1e6);
		}
	}
}
