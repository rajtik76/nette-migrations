<?php declare(strict_types=1);

namespace Rajtik\Migrations\Command;

use Rajtik\Console\Command\ExitCode;
use Rajtik\Migrations\Migration\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(name: 'migrate:reset', description: 'Roll back all migrations')]
final class MigrateResetCommand extends DatabaseCommand
{
	public function __construct(
		private readonly Migrator $migrator,
	) {
		parent::__construct();
	}


	protected function handle(InputInterface $input, OutputInterface $output): ExitCode
	{
		if (!$this->confirmToProceed($input, $output)) {
			return ExitCode::Failure;
		}

		if ($this->migrator->reset($this->printProgress($output, 'Rolled back')) === []) {
			$output->writeln('Nothing to roll back.');
		}

		return ExitCode::Success;
	}
}
