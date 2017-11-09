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
namespace BEdita\API\Test\TestCase\Controller;

use BEdita\API\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

/**
 * @coversDefaultClass \BEdita\API\Controller\PropertiesController
 */
class PropertiesControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.property_types',
        'plugin.BEdita/Core.properties',
    ];

    /**
     * Test index method.
     *
     * @return void
     *
     * @covers ::index()
     * @covers ::initialize()
     */
    public function testIndex()
    {
        $expected = [
            'links' => [
                'self' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'first' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'last' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'prev' => null,
                'next' => null,
                'home' => 'http://api.example.com/home',
            ],
            'meta' => [
                'pagination' => [
                    'count' => 9,
                    'page' => 1,
                    'page_count' => 1,
                    'page_items' => 9,
                    'page_size' => 20,
                ],
            ],
            'data' => [
                [
                    'id' => '1',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_title',
                        'description' => null,
                        'property_type_name' => 'string',
                        'object_type_name' => 'documents',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/1',
                    ],
                ],
                [
                    'id' => '2',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_description',
                        'description' => null,
                        'property_type_name' => 'string',
                        'object_type_name' => 'documents',
                        'label' => 'Brief description',
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/2',
                    ],
                ],
                [
                    'id' => '3',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_username',
                        'description' => 'Username, unique string',
                        'property_type_name' => 'string',
                        'object_type_name' => 'users',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/3',
                    ],
                ],
                [
                    'id' => '4',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_email',
                        'description' => 'User email',
                        'property_type_name' => 'string',
                        'object_type_name' => 'users',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/4',
                    ],
                ],
                [
                    'id' => '5',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_birthdate',
                        'description' => null,
                        'property_type_name' => 'date',
                        'object_type_name' => 'profiles',
                        'label' => 'Date of birth',
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                    'format' => 'date-time',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/5',
                    ],
                ],
                [
                    'id' => '6',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'another_surname',
                        'description' => null,
                        'property_type_name' => 'string',
                        'object_type_name' => 'profiles',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2016-12-31T23:09:23+00:00',
                        'modified' => '2016-12-31T23:09:23+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/6',
                    ],
                ],
                [
                    'id' => '7',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'disabled_property',
                        'description' => 'Disabled property example',
                        'property_type_name' => 'string',
                        'object_type_name' => 'files',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2017-09-05T11:10:00+00:00',
                        'modified' => '2017-09-05T11:10:00+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/7',
                    ],
                ],
                [
                    'id' => '8',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'media_property',
                        'description' => null,
                        'property_type_name' => 'string',
                        'object_type_name' => 'media',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2017-11-07T18:32:00+00:00',
                        'modified' => '2017-11-07T18:32:00+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/8',
                    ],
                ],
                [
                    'id' => '9',
                    'type' => 'properties',
                    'attributes' => [
                        'name' => 'files_property',
                        'description' => null,
                        'property_type_name' => 'string',
                        'object_type_name' => 'files',
                        'label' => null,
                        'is_nullable' => true,
                    ],
                    'meta' => [
                        'created' => '2017-11-07T18:32:00+00:00',
                        'modified' => '2017-11-07T18:32:00+00:00',
                        'schema' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'self' => 'http://api.example.com/model/properties/9',
                    ],
                ],
            ],
        ];

        $this->configRequestHeaders();
        $this->get('/model/properties?filter[type]=dynamic');
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertResponseCode(200);
        $this->assertContentType('application/vnd.api+json');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test index method.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testEmpty()
    {
        $expected = [
            'links' => [
                'self' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'first' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'last' => 'http://api.example.com/model/properties?' . http_build_query(['filter' => ['type' => 'dynamic']]),
                'prev' => null,
                'next' => null,
                'home' => 'http://api.example.com/home',
            ],
            'meta' => [
                'pagination' => [
                    'count' => 0,
                    'page' => 1,
                    'page_count' => 1,
                    'page_items' => 0,
                    'page_size' => 20,
                ],
            ],
            'data' => [],
        ];

        TableRegistry::get('Properties')->deleteAll([]);

        $this->configRequestHeaders();
        $this->get('/model/properties?filter[type]=dynamic');
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertResponseCode(200);
        $this->assertContentType('application/vnd.api+json');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test view method.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testSingle()
    {
        $expected = [
            'links' => [
                'self' => 'http://api.example.com/model/properties/1',
                'home' => 'http://api.example.com/home',
            ],
            'data' => [
                'id' => '1',
                'type' => 'properties',
                'attributes' => [
                    'name' => 'another_title',
                    'description' => null,
                    'property_type_name' => 'string',
                    'object_type_name' => 'documents',
                    'label' => null,
                    'is_nullable' => true,
                ],
                'meta' => [
                    'created' => '2016-12-31T23:09:23+00:00',
                    'modified' => '2016-12-31T23:09:23+00:00',
                    'schema' => [
                        'oneOf' => [
                            [
                                'type' => 'null',
                            ],
                            [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->configRequestHeaders();
        $this->get('/model/properties/1');
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertResponseCode(200);
        $this->assertContentType('application/vnd.api+json');
        static::assertEquals($expected, $result);
    }

    /**
     * Test view method.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testMissing()
    {
        $expected = [
            'links' => [
                'self' => 'http://api.example.com/model/properties/999999',
                'home' => 'http://api.example.com/home',
            ],
            'error' => [
                'status' => '404',
            ],
        ];

        $this->configRequestHeaders();
        $this->get('/model/properties/999999');
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertResponseCode(404);
        $this->assertContentType('application/vnd.api+json');
        $this->assertArrayNotHasKey('data', $result);
        $this->assertArrayHasKey('links', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals($expected['links'], $result['links']);
        $this->assertArraySubset($expected['error'], $result['error']);
        $this->assertArrayHasKey('title', $result['error']);
        $this->assertNotEmpty($result['error']['title']);
    }

    /**
     * Test add method.
     *
     * @return void
     *
     * @covers ::index()
     * @covers ::initialize()
     * @covers ::resourceUrl()
     */
    public function testAdd()
    {
        $data = [
            'type' => 'properties',
            'attributes' => [
                'name' => 'yet_another_body',
                'object_type_name' => 'documents',
                'property_type_name' => 'string',
                'description' => null,
            ],
        ];

        $this->configRequestHeaders('POST', $this->getUserAuthHeader());
        $this->post('/model/properties', json_encode(compact('data')));

        $this->assertResponseCode(201);
        $this->assertContentType('application/vnd.api+json');
        $this->assertHeader('Location', 'http://api.example.com/model/properties/10');
        static::assertTrue(TableRegistry::get('Properties')->exists(['name' => 'yet_another_body']));
    }

    /**
     * Test add method with invalid data.
     *
     * @return void
     *
     * @covers ::index()
     * @covers ::initialize()
     */
    public function testInvalidAdd()
    {
        $data = [
            'type' => 'properties',
            'attributes' => [
                'description' => 'Undefined property',
            ],
        ];

        $count = TableRegistry::get('Properties')->find()->count();

        $this->configRequestHeaders('POST', $this->getUserAuthHeader());
        $this->post('/model/properties', json_encode(compact('data')));

        $this->assertResponseCode(400);
        $this->assertContentType('application/vnd.api+json');
        $this->assertEquals($count, TableRegistry::get('Properties')->find()->count());
    }

    /**
     * Test edit method.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testEdit()
    {
        $data = [
            'id' => '1',
            'type' => 'properties',
            'attributes' => [
                'name' => 'another_title',
                'description' => 'nice description',
            ],
        ];

        $this->configRequestHeaders('PATCH', $this->getUserAuthHeader());
        $this->patch('/model/properties/1', json_encode(compact('data')));

        $this->assertResponseCode(200);
        $this->assertContentType('application/vnd.api+json');
        $this->assertEquals('nice description', TableRegistry::get('Properties')->get(1)->get('description'));
    }

    /**
     * Test edit method with ID conflict.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testConflictEdit()
    {
        $data = [
            'id' => '1',
            'type' => 'properties',
            'attributes' => [
                'name' => 'profile new',
            ],
        ];

        $this->configRequestHeaders('PATCH', $this->getUserAuthHeader());
        $this->patch('/model/properties/2', json_encode(compact('data')));

        $this->assertResponseCode(409);
        $this->assertContentType('application/vnd.api+json');
        $this->assertEquals('another_title', TableRegistry::get('Properties')->get(1)->get('name'));
        $this->assertEquals('another_description', TableRegistry::get('Properties')->get(2)->get('name'));
    }

    /**
     * Test delete method.
     *
     * @return void
     *
     * @covers ::resource()
     * @covers ::initialize()
     */
    public function testDelete()
    {
        $this->configRequestHeaders('DELETE', $this->getUserAuthHeader());
        $this->delete('/model/properties/1');

        $this->assertResponseCode(204);
        $this->assertContentType('application/vnd.api+json');
        $this->assertFalse(TableRegistry::get('Properties')->exists(['id' => 1]));
    }
}
