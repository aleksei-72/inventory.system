<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210218194649 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT extract(epoch from now())');
        $this->addSql('ALTER TABLE "user" ADD is_blocked BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT extract(epoch from now())');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP is_blocked');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT epoch');
    }
}
