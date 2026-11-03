<?php declare(strict_types=1);

namespace Rajtik\Migrations\Migration;

use Nette\Utils\Finder;


final readonly class MigrationFiles
{
	public function __construct(
		private string $directory,
	) {
	}


	/** @return array<string, string> name => file, sorted by name */
	public function all(): array
	{
		if (!is_dir($this->directory)) {
			return [];
		}

		$files = [];
		foreach (Finder::findFiles('*.php')->in($this->directory) as $file) {
			$files[$file->getBasename('.php')] = $file->getPathname();
		}

		ksort($files, SORT_STRING);
		return $files;
	}


	public function load(string $name): Migration
	{
		$file = $this->all()[$name] ?? throw new MigrationException("Migration '$name' not found in '$this->directory'.");
		$migration = require $file;

		return $migration instanceof Migration
			? $migration
			: throw new MigrationException("Migration file '$file' must return an instance of " . Migration::class . '.');
	}
}
