<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UniPotsdam\Orcid\Services\OrcidIdProvider;
use UniPotsdam\Orcid\Services\OrcidSyncService;
use UniPotsdam\Orcid\Value\OrcidId;

class SyncOrcidCommand extends Command
{
    public function __construct(
        private readonly OrcidSyncService $syncService,
        private readonly OrcidIdProvider $orcidIdProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('orcid', null, InputOption::VALUE_OPTIONAL, 'Sync only a specific ORCID iD')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $single = trim((string)$input->getOption('orcid'));
        if ($single !== '') {
            $orcids = [OrcidId::fromString($single)];
        } else {
            $orcids = $this->orcidIdProvider->findAllOrcidIds();
        }
        if ($orcids === []) {
            $output->writeln('<info>No ORCID IDs found in content.</info>');

            return Command::SUCCESS;
        }

        foreach ($orcids as $orcidId) {
            $output->writeln('<info>Syncing ' . $orcidId . '</info>');
            $this->syncService->syncOrcid($orcidId);
        }

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }
}
