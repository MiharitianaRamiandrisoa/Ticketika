<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les comptes utilisateurs, les catégories configurables et rattache les commandes aux comptes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_3AF34668989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, organizer TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql("INSERT INTO categories (name, slug, active) VALUES ('Concert', 'musique', 1), ('Spectacle', 'spectacle', 1), ('Famille', 'famille', 1), ('Atelier', 'ateliers', 1)");
        $this->addSql("ALTER TABLE events ADD category_id INT DEFAULT NULL");
        $this->addSql("UPDATE events SET category = 'famille' WHERE category = 'sport'");
        $this->addSql('UPDATE events e INNER JOIN categories c ON c.slug = e.category SET e.category_id = c.id');
        $this->addSql('ALTER TABLE events DROP category');
        $this->addSql('ALTER TABLE events MODIFY category_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_5387574A12469DE2 ON events (category_id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');

        $this->addSql('ALTER TABLE orders ADD user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_E52FFDEE9D86650F ON orders (user_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9D86650F FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9D86650F');
        $this->addSql('DROP INDEX IDX_E52FFDEE9D86650F ON orders');
        $this->addSql('ALTER TABLE orders DROP user_id');

        $this->addSql("ALTER TABLE events ADD category VARCHAR(30) NOT NULL DEFAULT 'famille'");
        $this->addSql('UPDATE events e INNER JOIN categories c ON c.id = e.category_id SET e.category = c.slug');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A12469DE2');
        $this->addSql('DROP INDEX IDX_5387574A12469DE2 ON events');
        $this->addSql('ALTER TABLE events DROP category_id');
        $this->addSql('ALTER TABLE events ALTER category DROP DEFAULT');

        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE categories');
    }
}
