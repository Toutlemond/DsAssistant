<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027184300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message ADD prompt_tokens INT DEFAULT NULL, ADD completion_tokens INT DEFAULT NULL, ADD total_tokens INT DEFAULT NULL, ADD token_details JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD total_tokens_used INT DEFAULT 0 NOT NULL, ADD prompt_tokens_used INT DEFAULT 0 NOT NULL, ADD completion_tokens_used INT DEFAULT 0 NOT NULL, ADD token_usage_stats JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP prompt_tokens, DROP completion_tokens, DROP total_tokens, DROP token_details');
        $this->addSql('ALTER TABLE user DROP total_tokens_used, DROP prompt_tokens_used, DROP completion_tokens_used, DROP token_usage_stats');
    }
}
