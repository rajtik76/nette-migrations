<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\ExitCode;
use Rajtik\Migrations\Seeding\SeederRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'db:seed', description: 'Seed the database')]
final class DbSeedCommand extends DatabaseCommand
{
	public function __construct(
		private readonly SeederRunner $runner,
		private readonly string $namespace,
		private readonly string $defaultSeeder,
	) {
		parent::__construct();
	}


	protected function configure(): void
	{
		parent::configure();
		$this->addOption('class', null, InputOption::VALUE_REQUIRED, 'Seeder class, short names are resolved in the seeders namespace', $this->defaultSeeder);
	}


	protected function handle(InputInterface $input, OutputInterface $output): ExitCode
	{
		if (!$this->confirmToProceed($input, $output)) {
			return ExitCode::Failure;
		}

		$class = (string) $input->getOption('class');
		if (!str_contains($class, '\\')) {
			$class = trim($this->namespace, '\\') . '\\' . $class;
		}

		if (!class_exists($class)) {
			$output->writeln("<error>Seeder class '$class' not found.</error>");
			return ExitCode::Failure;
		}

		$this->runner->run($class, $this->printProgress($output, 'Seeded'));
		return ExitCode::Success;
	}
}
