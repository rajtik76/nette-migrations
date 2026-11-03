<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\GeneratorCommand;
use Rajtik\Console\Generator\FileGenerator;
use Rajtik\Migrations\Support\Inflector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;


#[AsCommand(name: 'make:migration', description: 'Create a new migration file')]
final class MakeMigrationCommand extends GeneratorCommand
{
	public function __construct(
		FileGenerator $generator,
		private readonly string $directory,
		?string $stubDirectory = null,
	) {
		parent::__construct($generator, stubDirectory: $stubDirectory);
	}


	protected function configure(): void
	{
		parent::configure();
		$this->addOption('create', null, InputOption::VALUE_REQUIRED, 'Table to be created');
		$this->addOption('table', null, InputOption::VALUE_REQUIRED, 'Table to be modified');
	}


	protected function stubName(InputInterface $input): string
	{
		return match ($this->intent((string) $input->getArgument('name'), $input)[0]) {
			'create' => 'migration.create.stub',
			'update' => 'migration.update.stub',
			default => 'migration.stub',
		};
	}


	protected function defaultStubDirectory(): string
	{
		return dirname(__DIR__) . '/stubs';
	}


	protected function targetFile(string $name, InputInterface $input): string
	{
		return $this->directory . '/' . date('Y_m_d_His') . '_' . $this->fileName($name) . '.php';
	}


	protected function replacements(string $name, InputInterface $input): array
	{
		return ['table' => $this->intent($name, $input)[1] ?? ''];
	}


	private function fileName(string $name): string
	{
		$snake = Inflector::snake($name);
		if (!preg_match('~^[a-z][a-z0-9_]*$~', $snake)) {
			throw new \InvalidArgumentException("'$name' is not a valid migration name.");
		}

		return $snake;
	}


	/** @return array{'create'|'update'|'blank', ?string} */
	private function intent(string $name, InputInterface $input): array
	{
		$create = $input->getOption('create');
		$table = $input->getOption('table');
		$snake = Inflector::snake($name);

		return match (true) {
			is_string($create) => ['create', $create],
			is_string($table) => ['update', $table],
			(bool) preg_match('~^create_(\w+)_table$~', $snake, $m) => ['create', $m[1]],
			(bool) preg_match('~_(?:to|from|in)_(\w+)_table$~', $snake, $m) => ['update', $m[1]],
			default => ['blank', null],
		};
	}
}
