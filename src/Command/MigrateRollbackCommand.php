<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\ExitCode;
use Rajtik\Migrations\Migration\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'migrate:rollback', description: 'Roll back the last batch of migrations')]
final class MigrateRollbackCommand extends DatabaseCommand
{
	public function __construct(
		private readonly Migrator $migrator,
	) {
		parent::__construct();
	}


	protected function configure(): void
	{
		parent::configure();
		$this->addOption('step', null, InputOption::VALUE_REQUIRED, 'Number of migrations to roll back instead of the last batch', '0');
	}


	protected function handle(InputInterface $input, OutputInterface $output): ExitCode
	{
		if (!$this->confirmToProceed($input, $output)) {
			return ExitCode::Failure;
		}

		$steps = (int) $input->getOption('step');
		if ($this->migrator->rollback($steps, $this->printProgress($output, 'Rolled back')) === []) {
			$output->writeln('Nothing to roll back.');
		}

		return ExitCode::Success;
	}
}
