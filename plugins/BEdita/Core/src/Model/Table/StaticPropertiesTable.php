<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Table;

use BEdita\Core\Model\Entity\ObjectType;
use BEdita\Core\Model\Entity\Property;
use BEdita\Core\Utility\Text;
use Cake\Cache\Cache;
use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use Cake\Database\Schema\TableSchema;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * Properties Model
 *
 * @property \Cake\ORM\Association\BelongsTo $PropertyTypes
 * @property \Cake\ORM\Association\BelongsTo $ObjectTypes
 *
 * @since 4.0.0
 */
class StaticPropertiesTable extends PropertiesTable
{

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setEntityClass(Property::class);

        // Use a unique table name for each instance. This avoids conflicts if we don't drop the temporary table.
        // Temporary tables can be safely left there, since they will be deleted as soon as the connection
        // to the database is closed.
        $this->setTable(sprintf(
            'static_properties_%016x',
            function_exists('random_int') ? random_int(0, PHP_INT_MAX) : mt_rand(0, PHP_INT_MAX)
        ));

        // Create the temporary table.
        $this->createTable();

        if ($this->getConnection()->getDriver() instanceof Postgres) {
            // If we're using PostgreSQL we must instruct CakePHP to use the correct schema (which is deduced by
            // the current connection) to describe the temporary table, otherwise it thinks that the table has
            // zero columns, and the ORM fails to create new entities and persist them.
            // This query must be executed after the temporary table has been created, because the schema
            // is not present at all until at least a temporary table has been created.
            $schema = $this->getConnection()
                ->execute("SELECT nspname FROM pg_namespace WHERE oid = pg_my_temp_schema();")
                ->fetch();
            $this->setTable(sprintf('%s.%s', $schema[0], $this->getTable()));
        }

        // Insert data into table.
        $this->addSchemaDetails();
    }

    /**
     * Create temporary table with same columns, options and indexes as `properties`.
     *
     * @return void
     */
    protected function createTable()
    {
        Log::debug('Using temporary table for static properties'); // Log for statistics purposes... :/

        $tableName = $this->getTable();
        $parentTable = TableRegistry::get('Properties');
        $safeRename = function ($indexOrConstraint) use ($tableName) {
            return preg_replace(
                '/^properties_/',
                sprintf('%s_', str_replace('_', '', $tableName)),
                $indexOrConstraint
            );
        };

        // Create new temporary table.
        $table = new TableSchema($tableName);
        $table->setTemporary(true);

        // Copy options, columns, indexes and constraints (except foreign keys) from `properties`.
        $schema = $parentTable->getSchema();
        $table->setOptions($schema->getOptions());
        foreach ($schema->columns() as $column) {
            $attributes = $schema->getColumn($column);
            if ($column === $parentTable->getPrimaryKey()) {
                // Use custom IDs.
                $attributes['type'] = 'uuid';
                $attributes['length'] = null;
            }
            $table->addColumn($column, $attributes);
        }
        foreach ($schema->indexes() as $index) {
            $table->addIndex($safeRename($index), $schema->getIndex($index));
        }
        foreach ($schema->constraints() as $constraint) {
            $attributes = $schema->getConstraint($constraint);
            if (empty($attributes['type']) || $attributes['type'] === $schema::CONSTRAINT_FOREIGN) {
                // Temporary tables can't have foreign key constraints in MySQL.
                // https://dev.mysql.com/doc/refman/5.7/en/create-table-foreign-keys.html
                continue;
            }
            $table->addConstraint($safeRename($constraint), $attributes);
        }

        // Execute SQL to create table. In MySQL the transaction is completely useless,
        // because `CREATE TABLE` implicitly implies a commit.
        $this->getConnection()->transactional(function (Connection $connection) use ($table) {
            foreach ($table->createSql($connection) as $statement) {
                $connection->execute($statement);
            }
        });
    }

    /**
     * Store schema details into the temporary table.
     *
     * @return void
     */
    protected function addSchemaDetails()
    {
        $properties = Cache::remember(
            'static_properties',
            function () {
                $schemaCollection = $this->getConnection()->getSchemaCollection();
                $result = $this->ObjectTypes->find()
                    ->select(
                        [$this->ObjectTypes->getPrimaryKey(), $this->ObjectTypes->getDisplayField()]
                    )
                    ->order(['tree_left' => 'ASC']) // Ensure parent tables are processed first!
                    ->reduce(
                        function (array $accumulator, ObjectType $objectType) use ($schemaCollection) {
                            $table = TableRegistry::get($objectType->alias);
                            $tableName = $table->getTable();
                            if (in_array($tableName, $accumulator['tables'])) {
                                // Table already processed. This happens for "null extensions", like documents that
                                // extend objects, but don't have their own table.
                                return $accumulator;
                            }

                            $accumulator['tables'][] = $tableName;
                            $accumulator['properties'] = array_merge(
                                $accumulator['properties'],
                                $this->prepareTableFields($objectType, $table)
                            );

                            return $accumulator;
                        },
                        [
                            'tables' => [],
                            'properties' => [],
                        ]
                    );

                return $result['properties'];
            },
            ObjectTypesTable::CACHE_CONFIG
        );
        $this->saveMany($properties);
    }

    /**
     * Return an array of Property entities that represent object type concrete fields.
     *
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type to be described.
     * @param \Cake\ORM\Table $table Table object.
     * @return \BEdita\Core\Model\Entity\Property[]
     */
    protected function prepareTableFields(ObjectType $objectType, Table $table)
    {
        $schema = $table->getConnection()
            ->getSchemaCollection()
            ->describe($table->getTable());

        $sampleEntity = $table->newEntity();
        $hiddenProperties = $sampleEntity->getHidden();

        $properties = [];
        foreach ($schema->columns() as $name) {
            if (in_array($name, (array)$table->getPrimaryKey()) || in_array($name, $hiddenProperties)) {
                continue;
            }

            $column = $schema->getColumn($name);

            $property = $this->newEntity(compact('name'));
            $property->id = Text::uuid5(sprintf('%s.%s', $objectType->name, $name));
            $property->set('object_type_id', $objectType->id);
            $property->set('property_type_id', $this->PropertyTypes->find()->firstOrFail()->id); // TODO
            $property->set('description', Hash::get($column, 'comment'));

            $properties[] = $property;
        }

        return $properties;
    }
}
