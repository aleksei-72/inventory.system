<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210306195857 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ADD price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE item DROP price');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT epoch');
    }
}
