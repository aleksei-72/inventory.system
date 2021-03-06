<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210321223339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ALTER category_id DROP NOT NULL');
        $this->addSql('ALTER TABLE item ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE item ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING CURRENT_TIMESTAMP(0)');
        $this->addSql('ALTER TABLE item ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE item ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING CURRENT_TIMESTAMP(0)');
        $this->addSql('ALTER TABLE "user" ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING CURRENT_TIMESTAMP(0)');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE item ALTER category_id SET NOT NULL');
        $this->addSql('ALTER TABLE item ALTER created_at TYPE INT');
        $this->addSql('ALTER TABLE item ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE item ALTER updated_at TYPE INT');
        $this->addSql('ALTER TABLE item ALTER updated_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE "user" ALTER created_at TYPE INT');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT epoch');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }
}
