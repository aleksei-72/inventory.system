<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210405224320 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('ALTER TABLE item ALTER number TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE item ALTER number DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70');
        $this->addSql('DROP INDEX IDX_64C19C1727ACA70');
        $this->addSql('ALTER TABLE category DROP parent_id');
        $this->addSql('ALTER TABLE item ALTER number TYPE BIGINT');
        $this->addSql('ALTER TABLE item ALTER number DROP DEFAULT');
    }
}
