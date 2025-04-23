<?php

/**
 * tests/Controller/Product/ProductControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Product;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\{ ElementBaseCommandController, ElementBaseQueryController };
use TDW\ACiencia\Controller\Product\{ ProductCommandController, ProductQueryController };
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class ProductControllerTest
 */
#[TestsAttr\CoversClass(ProductCommandController::class)]
#[TestsAttr\CoversClass(ProductQueryController::class)]
#[TestsAttr\CoversClass(ElementBaseCommandController::class)]
#[TestsAttr\CoversClass(ElementBaseQueryController::class)]
class ProductControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de productos */
    protected const RUTA_API = '/api/v1/products';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];

        // load user admin fixtures
        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            true
        );

        // load user reader fixtures
        self::$reader['id'] = Utils::loadUserData(
            self::$reader['username'],
            self::$reader['email'],
            self::$reader['password'],
            false
        );
    }

    /**
     * Test GET /products 404 NOT FOUND
     */
    public function testCGetProducts404NotFound(): void
    {
        self::$writer['authHeader'] =
            $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test POST /products 201 CREATED
     *
     * @return array<string,string|int> ProductData
     * @throws JsonException
     */
    #[TestsAttr\Depends('testCGetProducts404NotFound')]
    public function testPostProduct201Created(): array
    {
        $p_data = [
            'name'      => self::$faker->words(3, true),
            'birthDate' => self::$faker->date(),
            'deathDate' => self::$faker->date(),
            'imageUrl'  => self::$faker->url(), // imageUrl(),
            'wikiUrl'   => self::$faker->url()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        self::assertSame(201, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Location'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProduct = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('product', $responseProduct);
        $productData = $responseProduct['product'];
        self::assertNotEquals(0, $productData['id']);
        self::assertSame($p_data['name'], $productData['name']);
        self::assertSame($p_data['birthDate'], $productData['birthDate']);
        self::assertSame($p_data['deathDate'], $productData['deathDate']);
        self::assertSame($p_data['imageUrl'], $productData['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $productData['wikiUrl']);

        return $productData;
    }

    /**
     * Test POST /users 422 UNPROCESSABLE ENTITY
     */
    #[TestsAttr\Depends('testCGetProducts404NotFound')]
    public function testPostProduct422UnprocessableEntity(): void
    {
        $p_data = [
            // 'name'      => self::$faker->words(3, true),
            'birthDate' => self::$faker->date(),
            'deathDate' => self::$faker->date(),
            'imageUrl'  => self::$faker->url(), // imageUrl(),
            'wikiUrl'   => self::$faker->url()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test POST /products 400 BAD REQUEST
     *
     * @param array<string,string|int> $product data returned by testPostProduct201Created()
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    public function testPostProduct400BadRequest(array $product): void
    {
        // Mismo username
        $p_data = [
            'name' => $product['name'],
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test GET /products 200 OK
     *
     * @param array<string,string|int> $product data returned by testPostProduct201Created()
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    public function testCGetProducts200Ok(array $product): array
    {
        self::assertIsString($product['name']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($product['name'], 0, -2),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $r_data);
        self::assertIsArray($r_data['products']);

        return $etag;
    }

    /**
     * Test GET /products 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetproducts200Ok
     */
    #[TestsAttr\Depends('testCGetProducts200Ok')]
    public function testCGetProducts304NotModified(array $etag): void
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /products/{productId} 200 OK
     *
     * @param array<string,string|int> $product data returned by testPostProduct201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    public function testGetProduct200Ok(array $product): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $product['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $product_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($product, $product_aux['product']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /products/{productId} 304 NOT MODIFIED
     *
     * @param array<string,string|int> $product data returned by testPostproduct201Created()
     * @param array<string> $etag returned by testGetproduct200Ok
     *
     * @return string Entity Tag
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    #[TestsAttr\Depends('testGetProduct200Ok')]
    public function testGetProduct304NotModified(array $product, array $etag): string
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $product['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /products/productname/{productname} 204 NO CONTENT
     *
     * @param array<string,string|int> $product data returned by testPostProduct201()
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    public function testGetProductname204NoContent(array $product): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/productname/' . $product['name']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /products/{productId}   209 UPDATED
     *
     * @param array<string,string|int> $product data returned by testPostProduct201Created()
     * @param string $etag returned by testGetProduct304NotModified
     *
     * @return array<string,string|int> modified product data
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    #[TestsAttr\Depends('testGetProduct304NotModified')]
    #[TestsAttr\Depends('testPostProduct400BadRequest')]
    #[TestsAttr\Depends('testCGetProducts304NotModified')]
    #[TestsAttr\Depends('testGetProductname204NoContent')]
    public function testPutProduct209Updated(array $product, string $etag): array
    {
        $p_data = [
            'name'  => self::$faker->words(3, true),
            'birthDate' => self::$faker->date(),
            'deathDate' => self::$faker->date(),
            'imageUrl'  => self::$faker->url(), // imageUrl(),
            'wikiUrl'   => self::$faker->url()
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $product['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $product_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('product', $product_aux);
        self::assertSame($product['id'], $product_aux['product']['id']);
        self::assertSame($p_data['name'], $product_aux['product']['name']);
        self::assertSame($p_data['birthDate'], $product_aux['product']['birthDate']);
        self::assertSame($p_data['deathDate'], $product_aux['product']['deathDate']);
        self::assertSame($p_data['imageUrl'], $product_aux['product']['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $product_aux['product']['wikiUrl']);

        return $product_aux['product'];
    }

    /**
     * Test PUT /products/{productId} 400 BAD REQUEST
     *
     * @param array<string,string|int> $product data returned by testPutProduct209Updated()
     */
    #[TestsAttr\Depends('testPutProduct209Updated')]
    public function testPutProduct400BadRequest(array $product): void
    {
        $p_data = [ 'name' => self::$faker->words(3, true) ];
        $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $product['id'],
            [],
            self::$writer['authHeader']
        );

        // productname already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $product['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /product/{productId} 428 PRECONDITION REQUIRED
     *
     * @param array<string,string|int> $product data returned by testPutProduct209Updated()
     */
    #[TestsAttr\Depends('testPutProduct209Updated')]
    public function testPutProduct428PreconditionRequired(array $product): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $product['id'],
            [],
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /products[/{productId}] NO CONTENT
     */
    public function testOptionsProduct204NoContent(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$faker->randomDigitNotNull()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test DELETE /products/{productId} 204 NO CONTENT
     *
     * @param array<string,string|int> $product data returned by testPostProduct201Created()
     *
     * @return int productId
     */
    #[TestsAttr\Depends('testPostProduct201Created')]
    #[TestsAttr\Depends('testPostProduct400BadRequest')]
    #[TestsAttr\Depends('testPostProduct422UnprocessableEntity')]
    #[TestsAttr\Depends('testPutProduct400BadRequest')]
    #[TestsAttr\Depends('testPutProduct428PreconditionRequired')]
    #[TestsAttr\Depends('testGetProductname204NoContent')]
    public function testDeleteProduct204NoContent(array $product): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $product['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $product['id'];
    }

    /**
     * Test GET /products/productname/{productname} 404 NOT FOUND
     *
     * @param array<string,string|int> $product data returned by testPutProduct209Updated()
     */
    #[TestsAttr\Depends('testPutProduct209Updated')]
    #[TestsAttr\Depends('testDeleteProduct204NoContent')]
    public function testGetProductname404NotFound(array $product): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/productname/' . $product['name']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /products/{productId} 404 NOT FOUND
     * Test PUT    /products/{productId} 404 NOT FOUND
     * Test DELETE /products/{productId} 404 NOT FOUND
     *
     * @param int $productId product id. returned by testDeleteProduct204NoContent()
     * @param string $method
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider404')]
    #[TestsAttr\Depends('testDeleteProduct204NoContent')]
    public function testProductStatus404NotFound(string $method, int $productId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $productId,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /products 401 UNAUTHORIZED
     * Test POST   /products 401 UNAUTHORIZED
     * Test PUT    /products/{productId} 401 UNAUTHORIZED
     * Test DELETE /products/{productId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider401')]
    public function testProductStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /products 403 FORBIDDEN
     * Test PUT    /products/{productId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /products/{productId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider403')]
    public function testProductStatus403Forbidden(string $method, string $uri, int $statusCode): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            $method,
            $uri,
            null,
            self::$reader['authHeader']
        );
        $this->internalTestError($response, $statusCode);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array<string,mixed> [ method, url ]
     */
    #[ArrayShape([
        'postAction401' => "string[]",
        'putAction401' => "string[]",
        'deleteAction401' => "string[]",
        ])]
    public static function routeProvider401(): array
    {
        return [
            // 'cgetAction401'   => [ 'GET',    self::RUTA_API ],
            // 'getAction401'    => [ 'GET',    self::RUTA_API . '/1' ],
            'postAction401'   => [ 'POST',   self::RUTA_API ],
            'putAction401'    => [ 'PUT',    self::RUTA_API . '/1' ],
            'deleteAction401' => [ 'DELETE', self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method ]
     */
    #[ArrayShape([
        'getAction404' => "string[]",
        'putAction404' => "string[]",
        'deleteAction404' => "string[]",
        ])]
    public static function routeProvider404(): array
    {
        return [
            'getAction404'    => [ 'GET' ],
            'putAction404'    => [ 'PUT' ],
            'deleteAction404' => [ 'DELETE' ],
        ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN (security) => 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method, url, statusCode ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array",
        ])]
    public static function routeProvider403(): array
    {
        return [
            'postAction403'   => [ 'POST',   self::RUTA_API, StatusCode::STATUS_FORBIDDEN ],
            'putAction403'    => [ 'PUT',    self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ],
            'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND  ],
        ];
    }
}
