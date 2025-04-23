<?php

/**
 * tests/Controller/User/UserControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\User;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\{Entity\Role, Utility\Utils};
use TDW\Test\ACiencia\Controller\BaseTestCase;

use function urlencode;

/**
 * Class UserControllerTest
 */
class UserControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de usuarios */
    private const RUTA_API = '/api/v1/users';

    /** @var array<string,mixed> writer user data */
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

        // load user admin (writer) fixtures
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
        );
    }

    /**
     * Test OPTIONS /users[/{userId}] 204 NO CONTENT
     */
    public function testOptionsUser204NoContent(): void
    {
        // OPTIONS /api/v1/users
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        // OPTIONS /api/v1/users/{id}
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$faker->randomDigitNotNull()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test POST /users 201 CREATED
     *
     * @return array<string,string|int> user data
     * @throws \JsonException
     */
    public function testPostUser201Created(): array
    {
        $p_data = [
            'username'  => self::$faker->userName(),
            'email'     => self::$faker->email(),
            'password'  => self::$faker->password(),
        ];
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);

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
        $responseUser = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        $newUserData = $responseUser['user'];
        self::assertNotEquals(0, $newUserData['id']);
        self::assertSame($p_data['username'], $newUserData['username']);
        self::assertSame($p_data['email'], $newUserData['email']);
        self::assertEquals(Role::INACTIVE->name, $newUserData['role']);

        return $newUserData;
    }

    /**
     * Test POST /users 422 UNPROCESSABLE ENTITY
     *
     * @param string|null $username
     * @param string|null $email
     * @param string|null $password
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    #[TestsAttr\DataProvider('dataProviderPostUser422')]
    public function testPostUser422UnprocessableEntity(?string $username, ?string $email, ?string $password): void
    {
        $p_data = [
            'username' => $username,
            'email'    => $email,
            'password' => $password,
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
     * Test POST /users 400 BAD REQUEST
     *
     * @param array<string,string|int> $user data returned by testPostUser201Created()
     * @return array<string,string|int>
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    public function testPostUser400BadRequest(array $user): array
    {
        // Mismo username
        $p_data = [
            'username' => $user['username'],
            'email'    => self::$faker->email(),
            'password' => self::$faker->password()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);

        // Mismo email
        $p_data = [
            'username' => self::$faker->userName(),
            'email'    => $user['email'],
            'password' => self::$faker->password()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);

        return $user;
    }

    /**
     * Test GET /users 200 OK
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    public function testCGetAllUsers200Ok(): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            self::$writer['authHeader']
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertStringContainsString('users', $r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('users', $r_data);
        self::assertIsArray($r_data['users']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /users 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetAllUsers200Ok
     */
    #[TestsAttr\Depends('testCGetAllUsers200Ok')]
    public function testCGetAllUsers304NotModified(array $etag): void
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
     * Test GET /users/{userId} 200 OK
     *
     * @param array<string,string|int> $user data returned by testPostUser201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    public function testGetUser200Ok(array $user): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $user['id'],
            null,
            self::$writer['authHeader']
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Headers: ' . json_encode($this->getTokenHeaders(), JSON_THROW_ON_ERROR)
        );
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($user, $user_aux['user']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /users/{userId} 304 NOT MODIFIED
     *
     * @param array<string,string|int> $user data returned by testPostUser201Created()
     * @param array<string> $etag returned by testGetUser200Ok
     *
     * @return string Entity Tag
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    #[TestsAttr\Depends('testGetUser200Ok')]
    public function testGetUser304NotModified(array $user, array $etag): string
    {
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $user['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /users/username/{username} 204 Ok
     *
     * @param array<string,string|int> $user data returned by testPostUser201Created()
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    public function testGetUsername204NoContent(array $user): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/username/' . $user['username']
        );

        self::assertSame(
            204,
            $response->getStatusCode(),
            'User: ' . json_encode($user, JSON_THROW_ON_ERROR)
        );
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /users/{userId}   209 UPDATED
     *
     * @param array<string,string|int> $user data returned by testPostUser201Created()
     * @param string $etag returned by testGetUser304NotModified()
     *
     * @return array<string,string|int> modified user data
     * @throws JsonException
     */
    #[TestsAttr\Depends('testPostUser201Created')]
    #[TestsAttr\Depends('testGetUser304NotModified')]
    #[TestsAttr\Depends('testPostUser400BadRequest')]
    #[TestsAttr\Depends('testCGetAllUsers304NotModified')]
    #[TestsAttr\Depends('testGetUsername204NoContent')]
    public function testPutUser209Updated(array $user, string $etag): array
    {
        $p_data = [
            'username'  => self::$faker->userName(),
            'email'     => self::$faker->email(),
            'password'  => self::$faker->password(),
            'role'      => (0 === self::$faker->numberBetween() % 2)
                ? Role::READER->name
                : Role::WRITER->name
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $user['id'],
            $p_data,
            array_merge(
                self::$writer['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );

        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($user['id'], $user_aux['user']['id']);
        self::assertSame($p_data['username'], $user_aux['user']['username']);
        self::assertSame($p_data['email'], $user_aux['user']['email']);
        self::assertEquals($p_data['role'], $user_aux['user']['role']);

        return $user_aux['user'];
    }

    /**
     * Test PUT /users/{userId} 400 BAD REQUEST
     *
     * @param array<string,string|int> $user data returned by testPutUser209Updated()
     */
    #[TestsAttr\Depends('testPutUser209Updated')]
    #[TestsAttr\Depends('testGetUser304NotModified')]
    public function testPutUser400BadRequest(array $user): void
    {
        $p_data = [
                ['username' => self::$reader['username']],   // username already exists
                ['email' => self::$reader['email']],         // e-mail already exists
                ['role' => self::$faker->word()],            // unexpected role
            ];
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $user['id'],
            [],
            self::$writer['authHeader']
        );
        $headers = array_merge(
            self::$writer['authHeader'],
            [ 'If-Match' => $r1->getHeader('ETag') ]
        );
        foreach ($p_data as $pair) {
            $response = $this->runApp(
                'PUT',
                self::RUTA_API . '/' . $user['id'],
                $pair,
                $headers
            );
            $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Test PUT /users/{userId} 428 PRECONDITION REQUIRED
     *
     * @param array<string,string|int> $user data returned by testPutUser209Updated()
     */
    #[TestsAttr\Depends('testPutUser209Updated')]
    public function testPutUser428PreconditionRequired(array $user): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $user['id'],
            [],
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test DELETE /users/{userId} 204 NO CONTENT
     *
     * @param array<string,string|int> $user data returned by testPostUser400BadRequest()
     *
     * @return int userId
     */
    #[TestsAttr\Depends('testPostUser400BadRequest')]
    #[TestsAttr\Depends('testPutUser428PreconditionRequired')]
    #[TestsAttr\Depends('testGetUsername204NoContent')]
    #[TestsAttr\Depends('testPutUser400BadRequest')]
    public function testDeleteUser204NoContent(array $user): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $user['id'],
            null,
            self::$writer['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $user['id'];
    }

    /**
     * Test GET /users/username/{username} 404 Not Found
     *
     * @param array<string,string|int> $user data returned by testPutUser209Updated()
     */
    #[TestsAttr\Depends('testPutUser209Updated')]
    #[TestsAttr\Depends('testDeleteUser204NoContent')]
    public function testGetUsername404NotFound(array $user): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/username/' . urlencode((string) $user['username'])
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /users 401 UNAUTHORIZED
     * Test GET    /users/{userId} 401 UNAUTHORIZED
     * Test DELETE /users/{userId} 401 UNAUTHORIZED
     * Test PUT    /users/{userId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider401')]
    public function testUserStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test GET    /users/{userId} 404 NOT FOUND
     * Test DELETE /users/{userId} 404 NOT FOUND
     * Test PUT    /users/{userId} 404 NOT FOUND
     *
     * @param int $userId user id. returned by testDeleteUser204()
     * @param string $method
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider404')]
    #[TestsAttr\Depends('testDeleteUser204NoContent')]
    public function testUserStatus404NotFound(string $method, int $userId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $userId,
            null,
            self::$writer['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test DELETE /users/{userId} 403 FORBIDDEN => 404 NOT FOUND
     * Test PUT    /users/{userId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     * @return void
     */
    #[TestsAttr\DataProvider('routeProvider403')]
    public function testUserStatus403Forbidden(string $method, string $uri, int $statusCode): void
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
     * @return array<string,mixed>
     */
    #[ArrayShape([
        'empty_data' => "array[null]",
        'no_username' => "array[string]",
        'no_email' => "array[string]",
        'no_passwd' => "array[string]",
        'no_us_pa' => "array[string]",
        'no_em_pa' => "array[string]",
        ])]
    public static function dataProviderPostUser422(): iterable
    {
        self::$faker = self::getFaker();
        $fakeUsername = self::$faker->userName();
        $fakeEmail = self::$faker->email();
        $fakePasswd = self::$faker->password();

        yield 'empty_data'  => [ null,          null,       null ];
        yield 'no_username' => [ null,          $fakeEmail, $fakePasswd ];
        yield 'no_email'    => [ $fakeUsername, null,       $fakePasswd ];
        yield 'no_passwd'   => [ $fakeUsername, $fakeEmail, null ];
        yield 'no_us_pa'    => [ null,          $fakeEmail, null ];
        yield 'no_em_pa'    => [ $fakeUsername, null,       null ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array<string,mixed> name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "string[]",
        'getAction401' => "string[]",
        'putAction401' => "string[]",
        'deleteAction401' => "string[]",
        ])]
    public static function routeProvider401(): iterable
    {
        yield 'cgetAction401'   => [ 'GET',    self::RUTA_API ];
        yield 'getAction401'    => [ 'GET',    self::RUTA_API . '/1' ];
        yield 'putAction401'    => [ 'PUT',    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ 'DELETE', self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> name => [ method ]
     */
    #[ArrayShape([
        'getAction404' => "string[]",
        'putAction404' => "string[]",
        'deleteAction404' => "string[]",
        ])]
    public static function routeProvider404(): iterable
    {
        yield 'getAction404'    => [ 'GET' ];
        yield 'putAction404'    => [ 'PUT' ];
        yield 'deleteAction404' => [ 'DELETE' ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN (security) => 404 NOT FOUND)
     *
     * @return array<string,mixed> name => [ method, url, statusCode ]
     */
    #[ArrayShape([
        'putAction403' => "array",
        'deleteAction403' => "array",
        ])]
    public static function routeProvider403(): iterable
    {
        yield 'putAction403'    => [ 'PUT',    self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ];
        yield 'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ];
    }
}
