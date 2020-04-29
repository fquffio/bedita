<?php
use Cake\ORM\Table;
use Migrations\AbstractMigration;

class LinksTable extends AbstractMigration
{

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        // links
        $this->table('links', ['id' => false])
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('url', 'string', [
                'comment' => 'Url',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('http_status', 'string', [
                'comment' => 'HTTP status',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('last_update', 'date', [
                'comment' => 'Last update date',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('links')
            ->addForeignKey(
                'id',
                'objects',
                'id',
                [
                    'constraint' => 'links_id_fk',
                    'update' => 'NO_ACTION',
                    'delete' => 'CASCADE'
                ]
            )
            ->update();

        $this->table('object_types')
            ->insert([
                [
                    'name' => 'links',
                    'singular' => 'link',
                    'description' => 'Links model',
                    'plugin' => 'BEdita/Core',
                    'model' => 'Links',
                    'core_type' => 1,
                    'enabled' => 0,
                ],
            ])
            ->save();
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->table('links')
            ->drop()
            ->save();

        $table = new Table([
            'table' => 'object_types',
            'connection' => $this->getAdapter()->getCakeConnection(),
        ]);
        $table->delete($table->find()->where(['name' => 'links'])->firstOrFail());
    }
}
