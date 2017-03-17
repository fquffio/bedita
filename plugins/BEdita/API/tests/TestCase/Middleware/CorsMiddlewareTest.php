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
namespace BEdita\API\Test\TestCase\Middleware;

use BEdita\API\Middleware\CorsMiddleware;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Response;

/**
 * Test for RoutingMiddleware
 */
class CorsMiddlewareTest extends TestCase
{
    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Data provider for `testCors` test case.
     *
     * @return array
     */
    public function corsProvider()
    {
        return [
            'noCors' => [
                200, // expectedStatus
                [], // expectedCorsHeaders
                [ // server request
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'GET',
                    'HTTP_ACCEPT' => 'application/json',
                ],
                [], // CORS config
            ],
            // delegate to server that in this case is not configured
            'noCorsPreflight' => [
                200,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://example.com'
                ],
                [],
            ],
            'allAllowedOrigin' => [
                200,
                [
                    'Access-Control-Allow-Origin' => '*'
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'GET',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://example.com'
                ],
                [
                    'allowOrigin' => '*'
                ],
            ],
            'specificAllowedOrigin' => [
                200,
                [
                    'Access-Control-Allow-Origin' => 'http://example.com',
                    'Vary' => 'Origin'
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'GET',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://example.com'
                ],
                [
                    'allowOrigin' => ['http://example.com', 'http://bedita.com']
                ],
            ],
            'notAllowedOrigin' => [
                403,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'GET',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com'
                ],
                [
                    'allowOrigin' => 'http://example.com'
                ],
            ],
            'preflightMissingOrigin' => [
                400,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                ],
                [
                    'allowOrigin' => '*'
                ],
            ],
            'preflightMissingControlRequestMethod' => [
                400,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                ],
                [
                    'allowOrigin' => '*'
                ],
            ],
            'preflightWrongControlRequestMethod' => [
                403,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT'
                ],
                [
                    'allowOrigin' => '*',
                    'allowMethods' => ['GET', 'POST']
                ],
            ],
            'preflightWrongControlHeaders' => [
                403,
                [],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
                ],
                [
                    'allowOrigin' => '*',
                    'allowMethods' => ['GET', 'POST'],
                    'allowHeaders' => ['Authorization']
                ],
            ],
            'preflightOk' => [
                200,
                [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
                ],
                [
                    'allowOrigin' => '*',
                    'allowMethods' => ['GET', 'POST'],
                    'allowHeaders' => ['Authorization', 'Content-Type']
                ],
            ],
            'preflightOk2' => [
                200,
                [
                    'Access-Control-Allow-Origin' => 'https://example.com',
                    'Access-Control-Allow-Methods' => 'GET, POST',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Expose-Headers' => 'X-Exposed, X-Exposed-Too',
                    'Access-Control-Max-Age' => '1000',
                    'Vary' => 'Origin'
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'https://example.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
                ],
                [
                    'allowOrigin' => 'https://example.com',
                    'allowMethods' => ['GET', 'POST'],
                    'allowHeaders' => ['Authorization', 'Content-Type'],
                    'allowCredentials' => true,
                    'exposeHeaders' => ['X-Exposed', 'X-Exposed-Too'],
                    'maxAge' => '1000'
                ],
            ],
            'simpleSkipPreflightConf' => [
                200,
                [
                    'Access-Control-Allow-Origin' => 'https://example.com',
                    'Access-Control-Allow-Methods' => '',
                    'Access-Control-Allow-Headers' => '',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Expose-Headers' => 'X-Exposed, X-Exposed-Too',
                    'Access-Control-Max-Age' => '',
                    'Vary' => 'Origin'
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'POST',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'https://example.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
                ],
                [
                    'allowOrigin' => 'https://example.com',
                    'allowMethods' => ['GET', 'POST'],
                    'allowHeaders' => ['Authorization', 'Content-Type'],
                    'allowCredentials' => true,
                    'exposeHeaders' => ['X-Exposed', 'X-Exposed-Too'],
                    'maxAge' => '1000'
                ],
            ],
            'preflightWildCardAllowMethods' => [
                200,
                [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type'
                ],
                [
                    'allowOrigin' => '*',
                    'allowMethods' => '*',
                    'allowHeaders' => ['Authorization', 'Content-Type']
                ],
            ],
            'preflightWildCardAllowHeaders' => [
                200,
                [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Test-Header',
                ],
                [
                    'REQUEST_URI' => '/testpath',
                    'REQUEST_METHOD' => 'OPTIONS',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_ORIGIN' => 'http://bedita.com',
                    'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                    'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type, X-Test-Header'
                ],
                [
                    'allowOrigin' => '*',
                    'allowMethods' => ['GET', 'POST'],
                    'allowHeaders' => '*'
                ],
            ],
        ];
    }

    /**
     * Test CORS response
     *
     * @param int $expectedStatus The HTTP status expected
     * @param array $expectedCorsHeaders The expected headers set from CORS conf
     * @param array $server The server vars (see $_SERVER)
     * @param array $corsConfig The specific CORS conf applied
     * @return void
     *
     * @dataProvider corsProvider
     * @covers \BEdita\API\Middleware\CorsMiddleware
     * @covers \BEdita\API\Network\CorsBuilder::build()
     */
    public function testCors($expectedStatus, $expectedCorsHeaders, $server, $corsConfig)
    {
        $request = ServerRequestFactory::fromGlobals($server);
        $response = new Response();
        $middleware = new CorsMiddleware($corsConfig);
        $middlewareQueue = (new MiddlewareQueue())
            ->add(new ErrorHandlerMiddleware())
            ->add($middleware);
        $response = (new Runner())->run($middlewareQueue, $request, $response);

        static::assertSame(!empty($corsConfig), $middleware->isConfigured());
        static::assertEquals($expectedStatus, $response->getStatusCode());

        $expectedCorsHeaders += [
            'Access-Control-Allow-Origin' => '',
            'Access-Control-Allow-Methods' => '',
            'Access-Control-Allow-Credentials' => '',
            'Access-Control-Allow-Headers' => '',
            'Access-Control-Expose-Headers' => '',
            'Access-Control-Max-Age' => '',
        ];
        foreach ($expectedCorsHeaders as $header => $value) {
            static::assertSame($value, $response->getHeaderLine($header));
        }
    }
}
