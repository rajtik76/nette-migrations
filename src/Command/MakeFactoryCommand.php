<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Cache\TempCache;
use Rajtik\Console\Command\GeneratorCommand;
use Rajtik\Console\Generator\ClassName;
use Rajtik\Console\Generator\FileGenerator;
use Rajtik\Migrations\Support\Inflector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;


#[AsCommand(name: 'make:factory', description: 'Create a new model factory')]
final class MakeFactoryCommand extends GeneratorCommand
{
	private const string Suffix = 'Factory';


	public function __construct(
		FileGenerator $generator,
		private readonly string $namespace,
		private readonly string $directory,
		?TempCache $cache = null,
		?string $stubDirectory = null,
	) {
		parent::__construct($generator, $cache, $stubDirectory);
	}


	protected function configure(): void
	{
		parent::configure();
		$this->addOption('table', null, InputOption::VALUE_REQUIRED, 'Table name, defaults to the plural of the factory name');
	}


	protected function stubName(InputInterface $input): string
	{
		return 'factory.stub';
	}


	protected function defaultStubDirectory(): string
	{
		return dirname(__DIR__) . '/stubs';
	}


	protected function targetFile(string $name, InputInterface $input): string
	{
		return $this->className($name)->file;
	}


	protected function replacements(string $name, InputInterface $input): array
	{
		$class = $this->className($name);
		$table = $input->getOption('table');

		return [
			'namespace' => $class->namespace,
			'class' => $class->shortName,
			'table' => is_string($table) ? $table : Inflector::plural(Inflector::snake($class->baseName(self::Suffix))),
		];
	}


	private function className(string $name): ClassName
	{
		return ClassName::resolve($name, $this->namespace, $this->directory, self::Suffix);
	}
}
