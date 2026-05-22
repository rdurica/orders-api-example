<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders and order_items tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE orders (
            id SERIAL NOT NULL,
            uuid UUID NOT NULL,
            partner_id VARCHAR(64) NOT NULL,
            order_id VARCHAR(64) NOT NULL,
            expected_delivery_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            rec_date_created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            rec_date_updated TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_orders_partner_order ON orders (partner_id, order_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_orders_uuid ON orders (uuid)');
        $this->addSql('CREATE INDEX idx_orders_partner_id ON orders (partner_id)');
        $this->addSql('CREATE INDEX idx_orders_order_id ON orders (order_id)');

        $this->addSql('CREATE TABLE order_items (
            id SERIAL NOT NULL,
            order_id INT NOT NULL,
            uuid UUID NOT NULL,
            product_id VARCHAR(64) NOT NULL,
            title VARCHAR(255) NOT NULL,
            price NUMERIC(12, 2) NOT NULL,
            quantity INT NOT NULL,
            rec_date_created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            rec_date_updated TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_order_items_uuid ON order_items (uuid)');
        $this->addSql('CREATE INDEX idx_order_items_order_id ON order_items (order_id)');
        $this->addSql(
            'ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order_id '
            . 'FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT fk_order_items_order_id');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
    }
}
