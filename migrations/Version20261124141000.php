<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les types SQL des colonnes de paiement avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE orders MODIFY ticket_selection JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE orders ALTER status SET DEFAULT 'PAID'");
        $this->addSql('ALTER TABLE orders MODIFY ticket_selection LONGTEXT NOT NULL');
    }
}
