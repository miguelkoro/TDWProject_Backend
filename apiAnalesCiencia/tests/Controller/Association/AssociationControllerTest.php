<?php

/**
 * tests/Controller/Association/AssociationControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Association;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\{ ElementBaseCommandController, ElementBaseQueryController };
use TDW\ACiencia\Controller\Person\{ AssociationCommandController, AssociationQueryController };
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class AssociationControllerTest
 */
#[TestsAttr\CoversClass(AssociationCommandController::class)]
#[TestsAttr\CoversClass(AssociationQueryController::class)]
#[TestsAttr\CoversClass(ElementBaseCommandController::class)]
#[TestsAttr\CoversClass(ElementBaseQueryController::class)]
class AssociationControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de asociaciones */
    protected const RUTA_API = '/api/v1/associations';

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
     * Test GET /associations 404 NOT FOUND
     */
    public function testCGetAssociations404NotFound(): void
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
     * Test POST /associations 201 CREATED
     *
     * @return array<string,string|int> AssociationData
     * @throws JsonException
     */
    #[TestsAttr\Depends('testCGetAssociations404NotFound')]
    public function testPostAssociation201Created(): array
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
        $responseAssociation = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        $associationData = $responseAssociation['association'];
        self::assertNotEquals(0, $associationData['id']);
        self::assertSame($p_data['name'], $associationData['name']);
        self::assertSame($p_data['birthDate'], $associationData['birthDate']);
        self::assertSame($p_data['deathDate'], $associationData['deathDate']);
        self::assertSame($p_data['imageUrl'], $associationData['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $associationData['wikiUrl']);

        return $associationData;
    }

    /**
     * Test POST /association 422 UNPROCESSABLE ENTITY
     */
    #[TestsAttr\Depends('testCGetAssociations404NotFound')]
    public function testPostAssociation422UnprocessableEntity(): void
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
     * Test POST /associations 400 BAD REQUEST
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    public function testPostAssociation400BadRequest(array $association): void
    {
        // Mismo name
        $p_data = [
            'name' => $association['name'],
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
     * Test GET /associations 200 OK
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    public function testCGetAssociations200Ok(array $association): array
    {
        self::assertIsString($association['name']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($association['name'], 0, -2),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('associations', $r_data);
        self::assertIsArray($r_data['associations']);

        return $etag;
    }

    /**
     * Test GET /associations 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetAssociations200Ok
     */
    #[TestsAttr\Depends('testCGetAssociations200Ok')]
    public function testCGetAssociations304NotModified(array $etag): void
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
     * Test GET /associations/{associationId} 200 OK
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    public function testGetAssociation200Ok(array $association): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $association['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $association_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($association, $association_aux['association']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /associations/{associationId} 304 NOT MODIFIED
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     * @param array<string> $etag returned by testGetAssociation200Ok
     *
     * @return string Entity Tag
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    #[TestsAttr\Depends('testGetAssociation200Ok')]
    public function testGetAssociation304NotModified(array $association, array $etag): string
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $association['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /associations/associationname/{associationname} 204 OK
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201()
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    public function testGetAssociationname204NoContent(array $association): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/associationname/' . $association['name']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /associations/{associationId}   209 UPDATED
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     * @param string $etag returned by testGetAssociation304NotModified
     *
     * @return array<string,string> modified association data
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    #[TestsAttr\Depends('testGetAssociation304NotModified')]
    #[TestsAttr\Depends('testPostAssociation400BadRequest')]
    #[TestsAttr\Depends('testCGetAssociations304NotModified')]
    #[TestsAttr\Depends('testGetAssociationname204NoContent')]
    public function testPutAssociation209Updated(array $association, string $etag): array
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
            self::RUTA_API . '/' . $association['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $association_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($association['id'], $association_aux['association']['id']);
        self::assertSame($p_data['name'], $association_aux['association']['name']);
        self::assertSame($p_data['birthDate'], $association_aux['association']['birthDate']);
        self::assertSame($p_data['deathDate'], $association_aux['association']['deathDate']);
        self::assertSame($p_data['imageUrl'], $association_aux['association']['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $association_aux['association']['wikiUrl']);

        return $association_aux['association'];
    }

    /**
     * Test PUT /associations/{associationId} 400 BAD REQUEST
     *
     * @param array<string,string|int> $association data returned by testPutAssociation209Updated()
     */
    #[TestsAttr\Depends('testPutAssociation209Updated')]
    public function testPutAssociation400BadRequest(array $association): void
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
            self::RUTA_API . '/' . $association['id'],
            [],
            self::$writer['authHeader']
        );

        // associationname already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $association['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /association/{associationId} 428 PRECONDITION REQUIRED
     *
     * @param array<string,string|int> $association data returned by testPutAssociation209Updated()
     */
    #[TestsAttr\Depends('testPutAssociation209Updated')]
    public function testPutAssociation428PreconditionRequired(array $association): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $association['id'],
            [],
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /associations[/{associationId}] 204 NO CONTENT
     */
    public function testOptionsAssociation204NoContent(): void
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
     * Test DELETE /associations/{associationId} 204 NO CONTENT
     *
     * @param array<string,string|int> $association data returned by testPostAssociation201Created()
     *
     * @return int associationId
     */
    #[TestsAttr\Depends('testPostAssociation201Created')]
    #[TestsAttr\Depends('testPostAssociation400BadRequest')]
    #[TestsAttr\Depends('testPostAssociation422UnprocessableEntity')]
    #[TestsAttr\Depends('testPutAssociation400BadRequest')]
    #[TestsAttr\Depends('testPutAssociation428PreconditionRequired')]
    #[TestsAttr\Depends('testGetAssociationname204NoContent')]
    public function testDeleteAssociation204NoContent(array $association): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $association['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $association['id'];
    }

    /**
     * Test GET /associations/associationname/{associationname} 404 NOT FOUND
     *
     * @param array<string,string|int> $association data returned by testPutAssociation209Updated()
     */
    #[TestsAttr\Depends('testPutAssociation209Updated')]
    #[TestsAttr\Depends('testDeleteAssociation204NoContent')]
    public function testGetAssociationname404NotFound(array $association): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/associationname/' . $association['name']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /associations/{associationId} 404 NOT FOUND
     * Test PUT    /associations/{associationId} 404 NOT FOUND
     * Test DELETE /associations/{associationId} 404 NOT FOUND
     *
     * @param int $associationId association id. returned by testDeleteAssociation204NoContent()
     * @param string $method
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider404')]
    #[TestsAttr\Depends('testDeleteAssociation204NoContent')]
    public function testAssociationStatus404NotFound(string $method, int $associationId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $associationId,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /associations 401 UNAUTHORIZED
     * Test POST   /associations 401 UNAUTHORIZED
     * Test GET    /associations/{associationId} 401 UNAUTHORIZED
     * Test PUT    /associations/{associationId} 401 UNAUTHORIZED
     * Test DELETE /associations/{associationId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider401')]
    public function testAssociationStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /associations 403 FORBIDDEN
     * Test PUT    /associations/{associationId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /associations/{associationId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider403')]
    public function testAssociationStatus403Forbidden(string $method, string $uri, int $statusCode): void
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
            'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ],
        ];
    }
}
