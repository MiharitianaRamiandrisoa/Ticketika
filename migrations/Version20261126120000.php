<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renforce la fiabilité des paiements : référence unique, idempotence des webhooks et expiration des réservations.';
    }

    public function up(Schema $schema): void
    {
        // L''index unique échouerait si des références non nulles étaient déjà dupliquées.
        // On arrête la migration avec un message explicite afin d''éviter une correction implicite.
        $duplicateReferences = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (SELECT payment_reference FROM orders WHERE payment_reference IS NOT NULL GROUP BY payment_reference HAVING COUNT(*) > 1) duplicates'
        );

        if ($duplicateReferences > 0) {
            throw new \RuntimeException('Migration interrompue : des payment_reference dupliquées existent déjà dans orders.');
        }

        $this->addSql('ALTER TABLE orders ADD reservation_expires_at DATETIME DEFAULT NULL');
        $this->addSql("UPDATE orders SET reservation_expires_at = DATE_ADD(created_at, INTERVAL 15 MINUTE) WHERE status = 'PENDING_PAYMENT' AND reservation_expires_at IS NULL");
        $this->addSql('CREATE INDEX IDX_E52FFDEE_STATUS_EXPIRATION ON orders (status, reservation_expires_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE_PAYMENT_REFERENCE ON orders (payment_reference)');

        // event_key doit contenir l''identifiant d''événement du prestataire.
        // Si Papi n''en fournit pas, le service devra calculer une clé stable à partir
        // du payload canonique, et non d''un identifiant aléatoire par tentative.
        $this->addSql('CREATE TABLE payment_webhook_events (id BIGINT AUTO_INCREMENT NOT NULL, provider VARCHAR(32) NOT NULL, event_key VARCHAR(191) NOT NULL, merchant_payment_reference VARCHAR(120) DEFAULT NULL, order_id INT DEFAULT NULL, payment_status VARCHAR(32) DEFAULT NULL, amount_ar INT DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, payload_hash CHAR(64) NOT NULL, received_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, processing_error VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_PAYMENT_WEBHOOK_PROVIDER_KEY (provider, event_key), INDEX IDX_PAYMENT_WEBHOOK_REFERENCE (merchant_payment_reference), INDEX IDX_PAYMENT_WEBHOOK_ORDER (order_id), INDEX IDX_PAYMENT_WEBHOOK_RECEIVED_AT (received_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_webhook_events ADD CONSTRAINT FK_PAYMENT_WEBHOOK_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_webhook_events DROP FOREIGN KEY FK_PAYMENT_WEBHOOK_ORDER');
        $this->addSql('DROP TABLE payment_webhook_events');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE_PAYMENT_REFERENCE ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE_STATUS_EXPIRATION ON orders');
        $this->addSql('ALTER TABLE orders DROP reservation_expires_at');
    }
}
