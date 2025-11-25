<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124221713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_AFDD94347E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game_list_player (game_list_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_DF9CFA49A86C69A4 (game_list_id), INDEX IDX_DF9CFA4999E6F5DF (player_id), PRIMARY KEY (game_list_id, player_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE game_list ADD CONSTRAINT FK_AFDD94347E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_list_player ADD CONSTRAINT FK_DF9CFA49A86C69A4 FOREIGN KEY (game_list_id) REFERENCES game_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_list_player ADD CONSTRAINT FK_DF9CFA4999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_232B318C7E3C61F9 ON game');
        $this->addSql('ALTER TABLE game ADD game_list_id INT NOT NULL, DROP number_of_players, DROP owner_id');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CA86C69A4 FOREIGN KEY (game_list_id) REFERENCES game_list (id)');
        $this->addSql('CREATE INDEX IDX_232B318CA86C69A4 ON game (game_list_id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7AD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A657E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_list DROP FOREIGN KEY FK_AFDD94347E3C61F9');
        $this->addSql('ALTER TABLE game_list_player DROP FOREIGN KEY FK_DF9CFA49A86C69A4');
        $this->addSql('ALTER TABLE game_list_player DROP FOREIGN KEY FK_DF9CFA4999E6F5DF');
        $this->addSql('DROP TABLE game_list');
        $this->addSql('DROP TABLE game_list_player');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CA86C69A4');
        $this->addSql('DROP INDEX IDX_232B318CA86C69A4 ON game');
        $this->addSql('ALTER TABLE game ADD owner_id INT NOT NULL, CHANGE game_list_id number_of_players INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_232B318C7E3C61F9 ON game (owner_id)');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADE48FD905');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7AD99E6F5DF');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A657E3C61F9');
    }
}
