<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927110759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute order.stripe_session_id : lie chaque commande à sa session Stripe Checkout en cours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD stripe_session_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP stripe_session_id');
    }
}
