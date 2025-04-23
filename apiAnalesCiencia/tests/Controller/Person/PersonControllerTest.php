<?php

/**
 * tests/Controller/Person/PersonControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Person;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\{ ElementBaseCommandController, ElementBaseQueryController };
use TDW\ACiencia\Controller\Person\{ PersonCommandController, PersonQueryController };
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class PersonControllerTest
 */
#[TestsAttr\CoversClass(PersonCommandController::class)]
#[TestsAttr\CoversClass(PersonQueryController::class)]
#[TestsAttr\CoversClass(ElementBaseCommandController::class)]
#[TestsAttr\CoversClass(ElementBaseQueryController::class)]
class PersonControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de personas */
    protected const RUTA_API = '/api/v1/persons';

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
     * Test GET /persons 404 NOT FOUND
     */
    public function testCGetPersons404NotFound(): void
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
     * Test POST /persons 201 CREATED
     *
     * @return array<string,string|int> PersonData
     * @throws JsonException
     */
    #[TestsAttr\Depends('testCGetPersons404NotFound')]
    public function testPostPerson201Created(): array
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
        $responsePerson = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        $personData = $responsePerson['person'];
        self::assertNotEquals(0, $personData['id']);
        self::assertSame($p_data['name'], $personData['name']);
        self::assertSame($p_data['birthDate'], $personData['birthDate']);
        self::assertSame($p_data['deathDate'], $personData['deathDate']);
        self::assertSame($p_data['imageUrl'], $personData['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $personData['wikiUrl']);

        return $personData;
    }

    /**
     * Test POST /persons 422 UNPROCESSABLE ENTITY
     */
    #[TestsAttr\Depends('testCGetPersons404NotFound')]
    public function testPostPerson422UnprocessableEntity(): void
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
     * Test POST /persons 400 BAD REQUEST
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    public function testPostPerson400BadRequest(array $person): void
    {
        // Mismo name
        $p_data = [
            'name' => $person['name'],
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
     * Test GET /persons 200 OK
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    public function testCGetPersons200Ok(array $person): array
    {
        self::assertIsString($person['name']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($person['name'], 0, -2),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $r_data);
        self::assertIsArray($r_data['persons']);

        return $etag;
    }

    /**
     * Test GET /persons 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetPersons200Ok
     */
    #[TestsAttr\Depends('testCGetPersons200Ok')]
    public function testCGetPersons304NotModified(array $etag): void
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
     * Test GET /persons/{personId} 200 OK
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    public function testGetPerson200Ok(array $person): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $person['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $person_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($person, $person_aux['person']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /persons/{personId} 304 NOT MODIFIED
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     * @param array<string> $etag returned by testGetPerson200Ok
     *
     * @return string Entity Tag
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    #[TestsAttr\Depends('testGetPerson200Ok')]
    public function testGetPerson304NotModified(array $person, array $etag): string
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $person['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /persons/personname/{personname} 204 OK
     *
     * @param array<string,string|int> $person data returned by testPostPerson201()
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    public function testGetPersonname204NoContent(array $person): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/personname/' . $person['name']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /persons/{personId}   209 UPDATED
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     * @param string $etag returned by testGetPerson304NotModified
     *
     * @return array<string,string> modified person data
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    #[TestsAttr\Depends('testGetPerson304NotModified')]
    #[TestsAttr\Depends('testPostPerson400BadRequest')]
    #[TestsAttr\Depends('testCGetPersons304NotModified')]
    #[TestsAttr\Depends('testGetPersonname204NoContent')]
    public function testPutPerson209Updated(array $person, string $etag): array
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
            self::RUTA_API . '/' . $person['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $person_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($person['id'], $person_aux['person']['id']);
        self::assertSame($p_data['name'], $person_aux['person']['name']);
        self::assertSame($p_data['birthDate'], $person_aux['person']['birthDate']);
        self::assertSame($p_data['deathDate'], $person_aux['person']['deathDate']);
        self::assertSame($p_data['imageUrl'], $person_aux['person']['imageUrl']);
        self::assertSame($p_data['wikiUrl'], $person_aux['person']['wikiUrl']);

        return $person_aux['person'];
    }

    /**
     * Test PUT /persons/{personId} 400 BAD REQUEST
     *
     * @param array<string,string|int> $person data returned by testPutPerson209Updated()
     */
    #[TestsAttr\Depends('testPutPerson209Updated')]
    public function testPutPerson400BadRequest(array $person): void
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
            self::RUTA_API . '/' . $person['id'],
            [],
            self::$writer['authHeader']
        );

        // personname already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $person['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /person/{personId} 428 PRECONDITION REQUIRED
     *
     * @param array<string,string|int> $person data returned by testPutPerson209Updated()
     */
    #[TestsAttr\Depends('testPutPerson209Updated')]
    public function testPutPerson428PreconditionRequired(array $person): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $person['id'],
            [],
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /persons[/{personId}] 204 NO CONTENT
     */
    public function testOptionsPerson204NoContent(): void
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
     * Test DELETE /persons/{personId} 204 NO CONTENT
     *
     * @param array<string,string|int> $person data returned by testPostPerson201Created()
     *
     * @return int personId
     */
    #[TestsAttr\Depends('testPostPerson201Created')]
    #[TestsAttr\Depends('testPostPerson400BadRequest')]
    #[TestsAttr\Depends('testPostPerson422UnprocessableEntity')]
    #[TestsAttr\Depends('testPutPerson400BadRequest')]
    #[TestsAttr\Depends('testPutPerson428PreconditionRequired')]
    #[TestsAttr\Depends('testGetPersonname204NoContent')]
    public function testDeletePerson204NoContent(array $person): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $person['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $person['id'];
    }

    /**
     * Test GET /persons/personname/{personname} 404 NOT FOUND
     *
     * @param array<string,string|int> $person data returned by testPutPerson209Updated()
     */
    #[TestsAttr\Depends('testPutPerson209Updated')]
    #[TestsAttr\Depends('testDeletePerson204NoContent')]
    public function testGetPersonname404NotFound(array $person): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/personname/' . $person['name']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /persons/{personId} 404 NOT FOUND
     * Test PUT    /persons/{personId} 404 NOT FOUND
     * Test DELETE /persons/{personId} 404 NOT FOUND
     *
     * @param int $personId person id. returned by testDeletePerson204NoContent()
     * @param string $method
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider404')]
    #[TestsAttr\Depends('testDeletePerson204NoContent')]
    public function testPersonStatus404NotFound(string $method, int $personId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $personId,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /persons 401 UNAUTHORIZED
     * Test POST   /persons 401 UNAUTHORIZED
     * Test GET    /persons/{personId} 401 UNAUTHORIZED
     * Test PUT    /persons/{personId} 401 UNAUTHORIZED
     * Test DELETE /persons/{personId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider401')]
    public function testPersonStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /persons 403 FORBIDDEN
     * Test PUT    /persons/{personId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /persons/{personId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider403')]
    public function testPersonStatus403Forbidden(string $method, string $uri, int $statusCode): void
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
