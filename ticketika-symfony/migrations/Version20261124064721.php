<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261124064721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(200) NOT NULL, category VARCHAR(30) NOT NULL, description LONGTEXT NOT NULL, starts_at DATETIME NOT NULL, place VARCHAR(120) NOT NULL, tag VARCHAR(40) DEFAULT NULL, capacity INT NOT NULL, sold_tickets INT NOT NULL, UNIQUE INDEX UNIQ_5387574A989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(180) NOT NULL, payment_method VARCHAR(20) NOT NULL, service_fee_ar INT NOT NULL, total_ar INT NOT NULL, created_at DATETIME NOT NULL, event_id INT NOT NULL, INDEX IDX_E52FFDEE71F7E88B (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket_types (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, price_ar INT NOT NULL, quantity INT NOT NULL, sold INT NOT NULL, note VARCHAR(160) DEFAULT NULL, event_id INT NOT NULL, INDEX IDX_7100EABB71F7E88B (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tickets (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(16) NOT NULL, scanned_at DATETIME DEFAULT NULL, order_id INT NOT NULL, ticket_type_id INT NOT NULL, UNIQUE INDEX UNIQ_54469DF477153098 (code), INDEX IDX_54469DF48D9F6D38 (order_id), INDEX IDX_54469DF4C980D5C1 (ticket_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE71F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('ALTER TABLE ticket_types ADD CONSTRAINT FK_7100EABB71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4C980D5C1 FOREIGN KEY (ticket_type_id) REFERENCES ticket_types (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE71F7E88B');
        $this->addSql('ALTER TABLE ticket_types DROP FOREIGN KEY FK_7100EABB71F7E88B');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF48D9F6D38');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4C980D5C1');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE ticket_types');
        $this->addSql('DROP TABLE tickets');
    }
}
