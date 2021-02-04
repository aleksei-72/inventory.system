<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210204164651 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    /*public function up_original(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE department (id INT NOT NULL, title VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE item (id INT NOT NULL, category_id INT NOT NULL, title VARCHAR(1024) NOT NULL, comment VARCHAR(1024) DEFAULT NULL, count INT NOT NULL, number BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1F1B251E12469DE2 ON item (category_id)');
        $this->addSql('CREATE TABLE item_room (item_id INT NOT NULL, room_id INT NOT NULL, PRIMARY KEY(item_id, room_id))');
        $this->addSql('CREATE INDEX IDX_BAAF1560126F525E ON item_room (item_id)');
        $this->addSql('CREATE INDEX IDX_BAAF156054177093 ON item_room (room_id)');
        $this->addSql('CREATE TABLE room (id INT NOT NULL, department_id INT NOT NULL, number VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_729F519BAE80F5DF ON room (department_id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_room ADD CONSTRAINT FK_BAAF1560126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_room ADD CONSTRAINT FK_BAAF156054177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BAE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }*/

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id SERIAL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE department (id SERIAL, title VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE item (id SERIAL, category_id INT NOT NULL, title VARCHAR(1024) NOT NULL, comment VARCHAR(1024) DEFAULT NULL, count INT NOT NULL, number BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1F1B251E12469DE2 ON item (category_id)');
        $this->addSql('CREATE TABLE item_room (item_id INT NOT NULL, room_id INT NOT NULL, PRIMARY KEY(item_id, room_id))');
        $this->addSql('CREATE INDEX IDX_BAAF1560126F525E ON item_room (item_id)');
        $this->addSql('CREATE INDEX IDX_BAAF156054177093 ON item_room (room_id)');
        $this->addSql('CREATE TABLE room (id SERIAL, department_id INT NOT NULL, number VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_729F519BAE80F5DF ON room (department_id)');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_room ADD CONSTRAINT FK_BAAF1560126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_room ADD CONSTRAINT FK_BAAF156054177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BAE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251E12469DE2');
        $this->addSql('ALTER TABLE room DROP CONSTRAINT FK_729F519BAE80F5DF');
        $this->addSql('ALTER TABLE item_room DROP CONSTRAINT FK_BAAF1560126F525E');
        $this->addSql('ALTER TABLE item_room DROP CONSTRAINT FK_BAAF156054177093');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_room');
        $this->addSql('DROP TABLE room');
    }
}
