<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi des paiements et les réservations de billets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE orders ADD status VARCHAR(32) NOT NULL DEFAULT 'PAID'");
        $this->addSql('ALTER TABLE orders ADD payment_provider VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD payment_reference VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD payment_token VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD ticket_selection LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE orders SET ticket_selection = '[]' WHERE ticket_selection IS NULL");
        $this->addSql('ALTER TABLE orders MODIFY ticket_selection LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP status');
        $this->addSql('ALTER TABLE orders DROP payment_provider');
        $this->addSql('ALTER TABLE orders DROP payment_reference');
        $this->addSql('ALTER TABLE orders DROP payment_token');
        $this->addSql('ALTER TABLE orders DROP ticket_selection');
    }
}
