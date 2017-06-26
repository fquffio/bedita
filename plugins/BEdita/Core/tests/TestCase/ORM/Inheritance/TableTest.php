<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\TestCase\ORM\Inheritance;

use BEdita\Core\ORM\Inheritance\AssociationCollection;
use BEdita\Core\ORM\Inheritance\Query;
use BEdita\Core\ORM\Inheritance\Table;
use Cake\Datasource\EntityInterface;
use Cake\ORM\AssociationCollection as CakeAssociationCollection;
use Cake\ORM\Table as CakeTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * {@see \BEdita\Core\ORM\Inheritance\Table} Test Case
 *
 * @coversDefaultClass \BEdita\Core\ORM\Inheritance\Table
 */
class TableTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.fake_animals',
        'plugin.BEdita/Core.fake_mammals',
        'plugin.BEdita/Core.fake_felines',
        'plugin.BEdita/Core.fake_articles',
    ];

    /**
     * Table FakeAnimals
     *
     * @var \BEdita\Core\ORM\Inheritance\Table
     */
    public $fakeAnimals;

    /**
     * Table FakeMammals
     *
     * @var \BEdita\Core\ORM\Inheritance\Table
     */
    public $fakeMammals;

    /**
     * Table FakeFelines
     *
     * @var \BEdita\Core\ORM\Inheritance\Table
     */
    public $fakeFelines;

    /**
     * Table options used for initialization
     *
     * @var array
     */
    protected $tableOptions = ['className' => Table::class];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->fakeFelines = TableRegistry::get('FakeFelines', $this->tableOptions);
        $this->fakeMammals = TableRegistry::get('FakeMammals', $this->tableOptions);
        $this->fakeAnimals = TableRegistry::get('FakeAnimals');
    }

    /**
     * Setup inheritance associations
     *
     * @return void
     */
    protected function setupAssociations()
    {
        $this->fakeMammals->extensionOf('FakeAnimals');
        $this->fakeFelines->extensionOf('FakeMammals');
        $this->fakeAnimals->hasMany('FakeArticles');
    }

    /**
     * Test query
     *
     * @return void
     *
     * @covers ::query()
     */
    public function testQuery()
    {
        static::assertInstanceOf(Query::class, $this->fakeFelines->query());
    }

    /**
     * Test inheritance setup.
     *
     * @return void
     *
     * @covers ::extensionOf()
     */
    public function testExtensionOf()
    {
        $this->fakeFelines->extensionOf('FakeAnimals');

        static::assertAttributeSame($this->fakeAnimals, 'inheritedTable', $this->fakeFelines);
        static::assertInstanceOf(AssociationCollection::class, $this->fakeFelines->associations());
    }

    /**
     * Test inheritance setup.
     *
     * @return void
     *
     * @covers ::extensionOf()
     * @covers ::inheritedTable()
     */
    public function testExtensionOfNotReady()
    {
        $this->fakeFelines->extensionOf('MyFakeAnimals');
        static::assertAttributeSame('MyFakeAnimals', 'inheritedTable', $this->fakeFelines);
        static::assertInstanceOf(CakeAssociationCollection::class, $this->fakeFelines->associations());

        $table = TableRegistry::get('MyFakeAnimals')->setTable('fake_animals');
        $inheritedTable = $this->fakeFelines->inheritedTable();

        static::assertSame($table, $inheritedTable);
        static::assertAttributeSame($table, 'inheritedTable', $this->fakeFelines);
        static::assertInstanceOf(AssociationCollection::class, $this->fakeFelines->associations());
    }

    /**
     * Test inherited tables
     *
     * @return void
     *
     * @covers ::inheritedTable()
     * @covers ::inheritedTables()
     */
    public function testInheritedTables()
    {
        static::assertEquals(null, $this->fakeFelines->inheritedTable());
        static::assertEquals([], $this->fakeFelines->inheritedTables());

        $this->setupAssociations();

        $mammalsInheritance = $this->fakeMammals->inheritedTable();
        static::assertEquals('FakeAnimals', $mammalsInheritance->getAlias());

        $felinesInheritance = $this->fakeFelines->inheritedTable();
        static::assertEquals('FakeMammals', $felinesInheritance->getAlias());

        $felinesDeepInheritance = array_map(function (CakeTable $inherited) {
            return $inherited->getAlias();
        }, $this->fakeFelines->inheritedTables());

        static::assertEquals(['FakeMammals', 'FakeAnimals'], $felinesDeepInheritance);
    }

    /**
     * Test method to find common inheritance tables.
     *
     * @return void
     *
     * @covers ::commonInheritance()
     */
    public function testCommonInheritance()
    {
        $this->setupAssociations();

        $expected = [$this->fakeMammals, $this->fakeAnimals];
        $common = $this->fakeFelines->commonInheritance($this->fakeMammals);
        $symmetricCommon = $this->fakeMammals->commonInheritance($this->fakeFelines);

        static::assertSame($expected, $common);
        static::assertSame($expected, $symmetricCommon);

        static::assertSame([], $this->fakeMammals->commonInheritance(TableRegistry::get('FakeArticles')));
    }

    /**
     * Test inherited tables
     *
     * @return void
     * @covers ::isTableInherited()
     */
    public function testIsTableInherited()
    {
        static::assertFalse($this->fakeFelines->isTableInherited('FakeMammals'));
        static::assertFalse($this->fakeFelines->isTableInherited('FakeMammals', true));

        $this->setupAssociations();
        static::assertTrue($this->fakeFelines->isTableInherited('FakeAnimals', true));
        static::assertFalse($this->fakeFelines->isTableInherited('FakeAnimals'));
        static::assertTrue($this->fakeFelines->isTableInherited('FakeMammals', true));
        static::assertTrue($this->fakeFelines->isTableInherited('FakeMammals'));
    }

    /**
     * testBasicFindWithoutInheritance
     *
     * @return void
     * @coversNothing
     */
    public function testBasicFindWithoutInheritance()
    {
        // find felines
        $felines = $this->fakeFelines->find();
        static::assertEquals(1, $felines->count());

        $feline = $felines->first();
        $expected = [
            'id' => 1,
            'family' => 'purring cats'
        ];
        $result = $feline->extract($felines->first()->visibleProperties());
        ksort($expected);
        ksort($result);
        static::assertEquals($expected, $result);
    }

    /**
     * testBasicFindWithInheritance
     *
     * @return void
     * @coversNothing
     */
    public function testBasicFindWithInheritance()
    {
        $this->setupAssociations();

        // find felines
        $felines = $this->fakeFelines->find();
        static::assertEquals(1, $felines->count());

        $feline = $felines->first();
        $expected = [
            'id' => 1,
            'name' => 'cat',
            'legs' => 4,
            'subclass' => 'Eutheria',
            'family' => 'purring cats'
        ];
        $result = $feline->extract($felines->first()->visibleProperties());
        ksort($expected);
        ksort($result);
        static::assertEquals($expected, $result);

        static::assertFalse($feline->dirty());

        // hydrate false
        $felines = $this->fakeFelines->find()->enableHydration(false);
        static::assertEquals(1, $felines->count());

        $result = $felines->first();
        ksort($expected);
        ksort($result);
        static::assertEquals($expected, $result);

        // find mammals
        $mammals = $this->fakeMammals->find()->enableHydration(false);
        static::assertEquals(2, $mammals->count());

        $expected = [
            [
                'id' => 1,
                'name' => 'cat',
                'legs' => 4,
                'subclass' => 'Eutheria'
            ],
            [
                'id' => 2,
                'name' => 'koala',
                'legs' => 4,
                'subclass' => 'Marsupial'
            ]
        ];
        $expected = array_map(function ($a) {
            ksort($a);

            return $a;
        }, $expected);

        $result = array_map(function ($a) {
            ksort($a);

            return $a;
        }, $mammals->toArray());
        static::assertEquals($expected, $result);
    }

    /**
     * Test find using contain
     *
     * @return void
     * @coversNothing
     */
    public function testContainFind()
    {
        $this->setupAssociations();

        $felines = $this->fakeFelines
            ->find()
            ->contain('FakeArticles');
        static::assertEquals(1, $felines->count());

        $feline = $felines->first();

        static::assertTrue($feline->has('fake_articles'));
        static::assertEquals(2, count($feline->get('fake_articles')));
        static::assertFalse($feline->dirty());

        $expected = [
            'id' => 1,
            'name' => 'cat',
            'legs' => 4,
            'subclass' => 'Eutheria',
            'family' => 'purring cats',
            'fake_articles' => [
                [
                    'id' => 1,
                    'title' => 'The cat',
                    'body' => 'article body',
                    'fake_animal_id' => 1
                ],
                [
                    'id' => 2,
                    'title' => 'Puss in boots',
                    'body' => 'text',
                    'fake_animal_id' => 1
                ]
            ]
        ];
        ksort($expected);

        $result = $feline->toArray();
        ksort($result);

        static::assertEquals($expected, $result);
    }

    /**
     * Data provider for `testFixClause` test case.
     *
     * @return array
     */
    public function selectProvider()
    {
        return [
            'fieldsFromAllInherited' => [
                ['family', 'subclass', 'name'],
                ['family', 'subclass', 'name']
            ],
            'fieldsFromAncestor' => [
                ['name'],
                ['name']
            ],
            'fieldsFromParent' => [
                ['subclass'],
                ['subclass']
            ],
        ];
    }

    /**
     * testSelect
     *
     * @param array $expected Expected result.
     * @param array $select Select clause.
     * @return void
     *
     * @dataProvider selectProvider
     * @coversNothing
     */
    public function testSelect($expected, $select)
    {
        $this->setupAssociations();

        $allColumns = $this->fakeFelines->getSchema()->columns();
        foreach ($this->fakeFelines->inheritedTables() as $t) {
            if (!($t instanceof CakeTable)) {
                static::fail('Unexpected table object');
            }

            $allColumns = array_merge($allColumns, $t->getSchema()->columns());
        }
        $allColumns = array_unique($allColumns);

        $unexpectedFields = array_diff($allColumns, $expected);

        $felines = $this->fakeFelines->find()->select($select);

        foreach ($felines as $f) {
            if (!($f instanceof EntityInterface)) {
                static::fail('Unexpected entity');
            }

            foreach ($expected as $field) {
                static::assertTrue($f->has($field));
            }

            foreach ($unexpectedFields as $field) {
                static::assertFalse($f->has($field));
            }
        }
    }

    /**
     * testClauses
     *
     * @return void
     * @coversNothing
     */
    public function testClauses()
    {
        $this->setupAssociations();

        // add some row
        $data = [
            'legs' => 4,
            'subclass' => 'Another Sublcass',
            'family' => 'big cats'
        ];

        foreach (['tiger', 'lion', 'leopard'] as $animal) {
            $data['name'] = $animal;
            $feline = $this->fakeFelines->newEntity($data);
            $this->fakeFelines->save($feline);
        }

        $query = $this->fakeFelines->find();
        $result = $query->select(['subclass', 'count' => $query->func()->count('*')])
            ->group(['subclass'])
            ->enableHydration(false);

        foreach ($result as $item) {
            if ($item['subclass'] == 'Eutheria') {
                static::assertEquals(1, $item['count']);
            } elseif ($item['subclass'] == 'Another Sublcass') {
                static::assertEquals(3, $item['count']);
            }
        }
    }

    /**
     * Provider for `testFindList`
     *
     * @return array
     */
    public function findListProvider()
    {
        return [
            'fieldsOnMain' => [
                [
                    1 => 'purring cats',
                    4 => 'big cats',
                    5 => 'big cats',
                    6 => 'big cats',
                ],
                [
                    'keyField' => 'id',
                    'valueField' => 'family'
                ],
                ['id' => 'asc']
            ],
            'fieldsOnMainAndParent' => [
                [
                    1 => 'Eutheria',
                    4 => 'Another Sublcass',
                    5 => 'Another Sublcass',
                    6 => 'Another Sublcass',
                ],
                [
                    'keyField' => 'id',
                    'valueField' => 'subclass'
                ],
                ['id' => 'asc']
            ],
            'fieldsOnParentAndAncestor' => [
                [
                    'cat' => 'Eutheria',
                    'leopard' => 'Another Sublcass',
                    'lion' => 'Another Sublcass',
                    'tiger' => 'Another Sublcass',
                ],
                [
                    'keyField' => 'name',
                    'valueField' => 'subclass'
                ],
                ['name' => 'asc']
            ],
            'fieldsOnAncestor' => [
                [
                    'cat' => 4,
                    'leopard' => 4,
                    'lion' => 4,
                    'tiger' => 4,
                ],
                [
                    'keyField' => 'name',
                    'valueField' => 'legs'
                ],
                ['name' => 'asc']
            ]
        ];
    }

    /**
     * testFindList
     *
     * @param array $expected Expected results.
     * @param array $listParams Options for `find('list')`.
     * @param array $order Order clause.
     * @return void
     *
     * @dataProvider findListProvider
     * @coversNothing
     */
    public function testFindList($expected, $listParams, $order)
    {
        $this->setupAssociations();

        // add some row
        $data = [
            'legs' => 4,
            'subclass' => 'Another Sublcass',
            'family' => 'big cats'
        ];

        foreach (['tiger', 'lion', 'leopard'] as $animal) {
            $data['name'] = $animal;
            $feline = $this->fakeFelines->newEntity($data);
            $this->fakeFelines->save($feline);
        }

        $query = $this->fakeFelines->find('list', $listParams);
        $query->order($order);

        $result = $query->toArray();
        static::assertEquals($expected, $result);
    }

    /**
     * Test `hasFinder` method.
     *
     * @return void
     *
     * @covers ::hasFinder()
     */
    public function testHasFinder()
    {
        $this->setupAssociations();

        static::assertFalse($this->fakeAnimals->hasFinder('children'));
        static::assertFalse($this->fakeMammals->hasFinder('children'));
        static::assertFalse($this->fakeFelines->hasFinder('children'));

        $this->fakeMammals->addBehavior('Tree');

        static::assertFalse($this->fakeAnimals->hasFinder('children'));
        static::assertTrue($this->fakeMammals->hasFinder('children'));
        static::assertTrue($this->fakeFelines->hasFinder('children'));
    }

    /**
     * Test `callFinder` method.
     *
     * @return void
     *
     * @covers ::callFinder()
     */
    public function testCallFinder()
    {
        $this->setupAssociations();

        $this->fakeAnimals->addBehavior('Tree');

        static::assertInstanceOf(Query::class, $this->fakeMammals->find('children', ['for' => 1, 'direct' => true]));
        static::assertInstanceOf(Query::class, $this->fakeFelines->find('children', ['for' => 1, 'direct' => true]));

        static::assertTextNotContains('FakeAnimals', debug($this->fakeMammals->find('children', ['for' => 1, 'direct' => true])->sql()));
        static::assertTextNotContains('FakeAnimals', debug($this->fakeFelines->find('children', ['for' => 1, 'direct' => true])->sql()));
    }

    /**
     * Test `callFinder` method.
     *
     * @return void
     *
     * @covers ::callFinder()
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Unknown finder method "gustavo"
     */
    public function testCallMissingFinder()
    {
        $this->fakeMammals->find('gustavo');
    }

    /**
     * Test `hasField` method.
     *
     * @return void
     *
     * @covers ::hasField()
     */
    public function testHasField()
    {
        $this->setupAssociations();

        static::assertTrue($this->fakeMammals->hasField('legs'));
        static::assertFalse($this->fakeMammals->hasField('legs', false));
        static::assertTrue($this->fakeAnimals->hasField('legs'));
    }

    /**
     * Test cloning of a table.
     *
     * @return void
     *
     * @covers ::__clone()
     */
    public function testClone()
    {
        $clone = clone $this->fakeMammals;

        static::assertEquals($clone->associations(), $this->fakeMammals->associations());
        static::assertNotSame($clone->associations(), $this->fakeMammals->associations());

        static::assertEquals($clone->behaviors(), $this->fakeMammals->behaviors());
        static::assertNotSame($clone->behaviors(), $this->fakeMammals->behaviors());

        static::assertEquals($clone->eventManager(), $this->fakeMammals->eventManager());
        static::assertNotSame($clone->eventManager(), $this->fakeMammals->eventManager());
    }
}