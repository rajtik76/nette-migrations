<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\GeneratorCommand;
use Rajtik\Console\Generator\ClassName;
use Rajtik\Console\Generator\FileGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;


#[AsCommand(name: 'make:seeder', description: 'Create a new seeder class')]
final class MakeSeederCommand extends GeneratorCommand
{
	public function __construct(
		FileGenerator $generator,
		private readonly string $namespace,
		private readonly string $directory,
		?string $stubDirectory = null,
	) {
		parent::__construct($generator, stubDirectory: $stubDirectory);
	}


	protected function stubName(InputInterface $input): string
	{
		return 'seeder.stub';
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

		return ['namespace' => $class->namespace, 'class' => $class->shortName];
	}


	private function className(string $name): ClassName
	{
		return ClassName::resolve($name, $this->namespace, $this->directory, 'Seeder');
	}
}
