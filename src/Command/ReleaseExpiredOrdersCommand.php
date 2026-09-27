<?php

namespace App\Command;

use App\Service\OrderReservationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Filet de sécurité à lancer en cron (ex. toutes les 15 min) : sans lui, une pièce
 * réservée par un client qui n'est jamais allé jusqu'au paiement resterait bloquée.
 */
#[AsCommand(
    name: 'app:orders:release-expired',
    description: 'Annule les commandes non payées trop anciennes et remet leurs pièces en vente',
)]
class ReleaseExpiredOrdersCommand extends Command
{
    public function __construct(private readonly OrderReservationService $reservationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // 45 min : au-delà de la durée de vie d'une session Stripe (30 min).
        $this->addOption('minutes', null, InputOption::VALUE_REQUIRED, 'Âge minimum des commandes à annuler', 45);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minutes = max(1, (int) $input->getOption('minutes'));
        $count = $this->reservationService->releaseExpired(new \DateTimeImmutable(sprintf('-%d minutes', $minutes)));

        (new SymfonyStyle($input, $output))->success(sprintf('%d commande(s) annulée(s).', $count));

        return Command::SUCCESS;
    }
}
