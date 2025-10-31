<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031074624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_loops (id INT AUTO_INCREMENT NOT NULL, command VARCHAR(255) NOT NULL, sleep INT DEFAULT 1 NOT NULL, max_processes INT DEFAULT 1 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_run_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message_send_tasks (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, text LONGTEXT NOT NULL, send_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', error_message LONGTEXT DEFAULT NULL, retry_count INT DEFAULT NULL, INDEX IDX_F6EA4C4EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE past_events_tasks (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event VARCHAR(255) NOT NULL, category VARCHAR(50) NOT NULL, interest_level VARCHAR(20) NOT NULL, original_context LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', suggested_remind_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actual_remind_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_processed TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_6B5B26C9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal_data (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, `key` VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, event_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9CF9F45EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message_send_tasks ADD CONSTRAINT FK_F6EA4C4EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE past_events_tasks ADD CONSTRAINT FK_6B5B26C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personal_data ADD CONSTRAINT FK_9CF9F45EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_send_tasks DROP FOREIGN KEY FK_F6EA4C4EA76ED395');
        $this->addSql('ALTER TABLE past_events_tasks DROP FOREIGN KEY FK_6B5B26C9A76ED395');
        $this->addSql('ALTER TABLE personal_data DROP FOREIGN KEY FK_9CF9F45EA76ED395');
        $this->addSql('DROP TABLE job_loops');
        $this->addSql('DROP TABLE message_send_tasks');
        $this->addSql('DROP TABLE past_events_tasks');
        $this->addSql('DROP TABLE personal_data');
    }
}
