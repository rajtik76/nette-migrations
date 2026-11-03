<?php declare(strict_types=1);

namespace Rajtik\Migrations\Seeding;


abstract class Seeder
{
	/** @var ?\Closure(class-string<Seeder>): void */
	private ?\Closure $caller = null;


	abstract public function run(): void;


	/** @param \Closure(class-string<Seeder>): void $caller */
	final public function setCaller(\Closure $caller): void
	{
		$this->caller = $caller;
	}


	/** @param class-string<Seeder>|list<class-string<Seeder>> $seeders */
	protected function call(string|array $seeders): void
	{
		$caller = $this->caller ?? throw new \LogicException('Seeder must be run through ' . SeederRunner::class . '.');

		foreach ((array) $seeders as $seeder) {
			$caller($seeder);
		}
	}
}
