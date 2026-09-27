<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927113654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Liste d\'attente des drops (waitlist_subscriber) et date d\'annonce par drop (category.waitlist_notified_at).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE waitlist_subscriber (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, unsubscribe_token VARCHAR(64) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_599B9DBCE7927C74 ON waitlist_subscriber (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_599B9DBCE0674361 ON waitlist_subscriber (unsubscribe_token)');
        $this->addSql('COMMENT ON COLUMN waitlist_subscriber.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE category ADD waitlist_notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN category.waitlist_notified_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE waitlist_subscriber');
        $this->addSql('ALTER TABLE category DROP waitlist_notified_at');
    }
}
