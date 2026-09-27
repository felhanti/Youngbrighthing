<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927113351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suivi d\'expédition : order.shipped_at et order.tracking_number.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD tracking_number VARCHAR(100) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "order".shipped_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP shipped_at');
        $this->addSql('ALTER TABLE "order" DROP tracking_number');
    }
}
