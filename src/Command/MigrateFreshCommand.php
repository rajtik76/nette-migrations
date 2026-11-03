<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\ExitCode;
use Rajtik\Migrations\Migration\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'migrate:fresh', description: 'Drop all tables and run all migrations')]
final class MigrateFreshCommand extends DatabaseCommand
{
	public function __construct(
		private readonly Migrator $migrator,
	) {
		parent::__construct();
	}


	protected function configure(): void
	{
		parent::configure();
		$this->addOption('seed', null, InputOption::VALUE_NONE, 'Run the database seeder afterwards');
	}


	protected function handle(InputInterface $input, OutputInterface $output): ExitCode
	{
		if (!$this->confirmToProceed($input, $output)) {
			return ExitCode::Failure;
		}

		$output->writeln('Dropped all tables.');
		$this->migrator->fresh($this->printProgress($output, 'Migrated'));

		return $input->getOption('seed') ? $this->seed($output) : ExitCode::Success;
	}
}
