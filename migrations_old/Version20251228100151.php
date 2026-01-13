<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251228100151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE address CHANGE is_default is_default TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE menu ADD stock INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY `FK_794381C68D9F6D38`');
        $this->addSql('DROP INDEX UNIQ_794381C68D9F6D38 ON review');
        $this->addSql('ALTER TABLE review DROP order_ref_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE address CHANGE is_default is_default TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE menu DROP stock');
        $this->addSql('ALTER TABLE review ADD order_ref_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT `FK_794381C68D9F6D38` FOREIGN KEY (order_ref_id) REFERENCES `order` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C68D9F6D38 ON review (order_ref_id)');
    }
}
