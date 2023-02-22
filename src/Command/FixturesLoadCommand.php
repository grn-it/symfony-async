<?php

namespace App\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Purge database, restart sequences (PostgreSQL) and load fixtures',
)]
class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Purge database, restart sequences (PostgreSQL) and load fixtures');

        if ($input->isInteractive()) {
            if (!$io->confirm(
                "Careful, database will be purged and sequences (PostgreSQL) restarted.\n Do you want to continue?",
                false
            )) {
                return Command::SUCCESS;
            }
        }

        $io->section('Restarting sequences (PostgreSQL)');

        $sequences = $this->em
            ->getConnection()
            ->executeQuery("SELECT c.relname FROM pg_class c WHERE (c.relkind = 'S')")
            ->fetchFirstColumn()
        ;

        foreach ($sequences as $sequence) {
            $this->em->getConnection()
                ->executeQuery('ALTER SEQUENCE ' . $sequence . ' RESTART WITH 1');

            $io->writeln($sequence);
        }

        $io->section('Purging database and loading fixtures');

        $command = $this->getApplication()->find('doctrine:fixtures:load');

        $arrayInput = new ArrayInput([]);
        $arrayInput->setInteractive(false);

        $returnCode = $command->run($arrayInput, $output);
        if ($returnCode !== Command::SUCCESS) {
            return $returnCode;
        }

        $io->newLine(2);
        $io->success('Fixtures loaded');

        return Command::SUCCESS;
    }
}
