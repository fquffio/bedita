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

namespace BEdita\Core\Test\TestCase\Model\Table;

use BEdita\Core\State\CurrentApplication;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * {@see \BEdita\Core\Model\Table\ConfigTable} Test Case
 *
 * @coversDefaultClass \BEdita\Core\Model\Table\ConfigTable
 */
class ConfigTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \BEdita\Core\Model\Table\ConfigTable
     */
    public $Config;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.Applications',
        'plugin.BEdita/Core.Config',
    ];

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->Config = TableRegistry::getTableLocator()->get('Config');
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->Config);

        parent::tearDown();
    }

    /**
     * Test initialization.
     *
     * @return void
     * @coversNothing
     */
    public function testInitialization()
    {
        $this->Config->initialize([]);
        $this->assertEquals('config', $this->Config->getTable());
        $this->assertEquals('name', $this->Config->getPrimaryKey());
    }

    /**
     * Data provider for `testValidation` test case.
     *
     * @return array
     */
    public function validationProvider()
    {
        return [
            'valid' => [
                true,
                [
                    'name' => 'KeyName',
                    'context' => 'Group1',
                    'content' => 'null'
                ],
            ],
            'notValid' => [
                false,
                [
                    'name' => 'missingContent',
                    'context' => '',
                ],
            ],
            'notValid2' => [
                false,
                [
                    'name' => 'bad.key',
                    'context' => 'somecontext',
                    'content' => 'some content'
                ],
            ],
        ];
    }

    /**
     * Test validation.
     *
     * @param bool $expected Expected result.
     * @param array $data Data to be validated.
     *
     * @return void
     * @dataProvider validationProvider
     * @coversNothing
     */
    public function testValidation($expected, array $data)
    {
        $config = $this->Config->newEntity($data);

        $error = (bool)$config->getErrors();
        $this->assertEquals($expected, !$error);

        if ($expected) {
            $success = $this->Config->save($config);
            $this->assertTrue((bool)$success);
        }
    }

    /**
     * Test `mine` finder
     *
     * @return void
     * @covers ::findMine()
     */
    public function testFindMine()
    {
        $config = $this->Config->find('mine')->toArray();
        $names = Hash::extract($config, '{n}.name');
        // `appVal` must not be present
        static::assertFalse(in_array('appVal', $names));

        CurrentApplication::setApplication(TableRegistry::getTableLocator()->get('Applications')->get(1));

        $config = $this->Config->find('mine')->toArray();
        $names = Hash::extract($config, '{n}.name');
        // `appVal` must be present
        static::assertTrue(in_array('appVal', $names));
    }
}
