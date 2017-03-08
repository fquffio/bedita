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

namespace BEdita\Core\Test\TestCase\Model\Behavior;

use BEdita\Core\Model\Entity\Relation;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * {@see \BEdita\Core\Model\Behavior\RelationsBehavior} Test Case
 *
 * @coversDefaultClass \BEdita\Core\Model\Behavior\RelationsBehavior
 */
class RelationsBehaviorTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.object_types',
        'plugin.BEdita/Core.relations',
        'plugin.BEdita/Core.relation_types',
        'plugin.BEdita/Core.objects',
        'plugin.BEdita/Core.profiles',
    ];

    /**
     * Test initial setup
     *
     * @return void
     *
     * @covers ::initialize()
     */
    public function testInitialization()
    {
        TableRegistry::clear();

        $Documents = TableRegistry::get('Documents');
        $Profiles = TableRegistry::get('Profiles');

        static::assertInstanceOf(BelongsToMany::class, $Documents->association('Test'));
        static::assertInstanceOf(BelongsToMany::class, $Documents->association('InverseTest'));
        static::assertInstanceOf(BelongsToMany::class, $Profiles->association('InverseTest'));
    }

    /**
     * Test getter of relations.
     *
     * @return void
     *
     * @covers ::getRelations()
     */
    public function testGetRelations()
    {
        $expected = [
            'test',
            'inverse_test',
        ];

        $Documents = TableRegistry::get('Documents');

        static::assertTrue($Documents->behaviors()->hasMethod('getRelations'));

        $relations = $Documents->behaviors()->call('getRelations');
        static::assertEquals($expected, array_keys($relations));
        foreach ($relations as $relation) {
            static::assertInstanceOf(Relation::class, $relation);
        }
    }
}