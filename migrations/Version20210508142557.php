<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210508142557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE import_transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE import_transaction (id INT NOT NULL, target_user_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, exec_time INT NOT NULL, status BOOLEAN DEFAULT \'false\' NOT NULL, date_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, count_items INT DEFAULT 0 NOT NULL, description VARCHAR(1024) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F64BCE316C066AFE ON import_transaction (target_user_id)');
        $this->addSql('ALTER TABLE import_transaction ADD CONSTRAINT FK_F64BCE316C066AFE FOREIGN KEY (target_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE import_transaction_id_seq CASCADE');
        $this->addSql('DROP TABLE import_transaction');
        $this->addSql('ALTER TABLE "user" ALTER is_blocked SET DEFAULT \'false\'');
    }
}
