<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210212114804 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C12B36786B ON category (title)');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT extract(epoch from now())');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_64C19C12B36786B');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT epoch');
    }
}
