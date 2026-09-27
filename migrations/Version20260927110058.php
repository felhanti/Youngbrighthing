<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927110058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme product.is_sold en product.available (le booléen signifiait déjà « disponible ») et met à jour les index messenger (Symfony 7.4).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product RENAME COLUMN is_sold TO available');
        $this->addSql('DROP INDEX idx_75ea56e016ba31db');
        $this->addSql('DROP INDEX idx_75ea56e0e3bd61ce');
        $this->addSql('DROP INDEX idx_75ea56e0fb7336f0');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE product RENAME COLUMN available TO is_sold');
    }
}
