<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210126151902 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE session (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, term BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('DROP TABLE user_token');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE user_token (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, term BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('DROP TABLE session');
    }
}
