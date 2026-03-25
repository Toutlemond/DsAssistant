<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324182223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE focus (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, topic VARCHAR(255) NOT NULL, source VARCHAR(50) NOT NULL, priority SMALLINT DEFAULT 5 NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, context JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_62C04AE9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thoughts (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, focus_id INT DEFAULT NULL, content LONGTEXT NOT NULL, type VARCHAR(50) DEFAULT \'insight\' NOT NULL, used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5F9B59E7A76ED395 (user_id), INDEX IDX_5F9B59E751804B42 (focus_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE focus ADD CONSTRAINT FK_62C04AE9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE thoughts ADD CONSTRAINT FK_5F9B59E7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE thoughts ADD CONSTRAINT FK_5F9B59E751804B42 FOREIGN KEY (focus_id) REFERENCES focus (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE focus DROP FOREIGN KEY FK_62C04AE9A76ED395');
        $this->addSql('ALTER TABLE thoughts DROP FOREIGN KEY FK_5F9B59E7A76ED395');
        $this->addSql('ALTER TABLE thoughts DROP FOREIGN KEY FK_5F9B59E751804B42');
        $this->addSql('DROP TABLE focus');
        $this->addSql('DROP TABLE thoughts');
    }
}
