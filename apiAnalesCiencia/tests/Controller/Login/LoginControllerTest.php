<?php

/**
 * tests/Controller/LoginControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Login;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Entity\Role;
use TDW\ACiencia\Utility\Utils;
use TDW\Test\ACiencia\Controller\BaseTestCase;

use function base64_decode;

/**
 * Class LoginControllerTest
 */
#[TestsAttr\CoversClass(\TDW\ACiencia\Controller\Login\OptionsQuery::class)]
#[TestsAttr\CoversClass(\TDW\ACiencia\Controller\Login\LoginController::class)]
class LoginControllerTest extends BaseTestCase
{
    private static string $ruta_base;   // path de login

    /** @var array<string,mixed> $writer */
    protected static array $writer;     // usuario writer
    /** @var array<string,mixed> $reader */
    protected static array $reader;     // usuario reader

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$ruta_base = $_ENV['RUTA_LOGIN'];

        // Fixture: writer
        self::$writer = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];

        // load user admin/writer fixtures
        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            isWriter: true
        );

        // Fixture: reader
        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];

        // load user reader fixtures
        self::$reader['id'] = Utils::loadUserData(
            self::$reader['username'],
            self::$reader['email'],
            self::$reader['password'],
            isWriter: false
        );
    }

    /**
     * Test OPTIONS /access_token 204 NO CONTENT
     */
    public function testOptionsUser204NoContent(): void
    {
        // OPTIONS /access_token
        $response = $this->runApp(
            'OPTIONS',
            self::$ruta_base
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test POST /access_token 404 NOT FOUND
     *
     * @param array<string,string>|null $data
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\DataProvider('proveedorUsuarios404')]
    public function testPostLogin404NotFound(?array $data): void
    {
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(StatusCode::STATUS_BAD_REQUEST, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        /** @var array<string,string> $r_data */
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $r_data);
        self::assertArrayHasKey('error_description', $r_data);
    }

    /**
     * Test POST /access_token 200 OK application/x-www-form-urlencoded
     * @throws JsonException
     */
    public function testPostLogin200OkUrlEncoded(): void
    {
        $data = [
            'username' => self::$writer['username'],
            'password' => self::$writer['password']
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertTrue($response->hasHeader('Authorization'));
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($r_data['access_token']);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertNotEmpty($r_data['expires_in']);
    }

    /**
     * Test POST /access_token 200 OK application/json
     * @throws JsonException
     */
    public function testPostLogin200OkApplicationJson(): void
    {
        $data = [
            'username' => self::$writer['username'],
            'password' => self::$writer['password']
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            json_encode($data, JSON_THROW_ON_ERROR),
            [ 'Content-Type' => 'application/json' ]
        );

        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertTrue($response->hasHeader('Authorization'));
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($r_data['access_token']);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertNotEmpty($r_data['expires_in']);
    }

    /**
     * @param string $user [ reader | writer ]
     * @param array<Role> $reqScopes
     * @param Role $expectedScope
     *
     * @throws JsonException
     */
    #[TestsAttr\DataProvider('proveedorAmbitos')]
    public function testLoginWithScopes200Ok(string $user, array $reqScopes, Role $expectedScope): void
    {
        $userData = ('reader' === $user) ? self::$reader : self::$writer;
        $requestedScopes = [];
        foreach ($reqScopes as $role) {
            $requestedScopes[] = $role->value;
        }
        $post_data = [
            'username' => $userData['username'],
            'password' => $userData['password'],
            'scope'    => implode('+', $requestedScopes),
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $post_data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Authorization'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertGreaterThan(0, $r_data['expires_in']);
        self::assertNotEmpty($r_data['access_token']);

        $payload = explode('.', $r_data['access_token']);
        $data = json_decode((string) base64_decode($payload[1], true), true, 512, JSON_THROW_ON_ERROR);
        self::assertContains($expectedScope->value, $data['scopes']);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Proveedor de usuarios 404
     *
     * @return array<string,mixed>
     */
    #[ArrayShape([
        'empty_user' => "array[array[string,string]]",
        'no_password' => "array[array[string,string]]",
        'no_username' => "array[array[string,string]]",
        'incorrect_username' => "array[array[string,string]]",
        'incorrect_passwd' => "array[array[string,string]]",
        ])]
    public static function proveedorUsuarios404(): iterable
    {
        self::$faker = self::getFaker();
        $fakeUsername = self::$faker->userName();
        $fakePasswd = self::$faker->password();

        yield 'no_data'  => [ null ];

        yield 'empty_user'  =>
                [ [ ] ];

        yield 'no_password' =>
                [ [ 'username' => $fakeUsername ] ];

        yield 'no_username' =>
                [ [ 'password' => $fakePasswd ] ];

        yield 'incorrect_username' =>
                [ [ 'username' => $fakeUsername, 'password' => $fakePasswd ] ];

        yield 'incorrect_passwd' =>
                [ [ 'username' => $fakeUsername, 'password' => $fakePasswd ] ];
    }

    /**
     * @return array<string,mixed> [userdata, requestedScope[], expectedScope]
     */
    public static function proveedorAmbitos(): iterable
    {
        return [
            'reader -- r' => ['reader', [], Role::READER],
            'reader r- r' => ['reader', [Role::READER], Role::READER],
            'reader rw r' => ['reader', [Role::READER, Role::WRITER], Role::READER],
            'reader -w r' => ['reader', [Role::WRITER], Role::READER],
            'writer -- r' => ['writer', [], Role::READER],
            'writer -- w' => ['writer', [], Role::WRITER],
            'writer r- r' => ['writer', [Role::READER], Role::READER],
            'writer -w r' => ['writer', [Role::WRITER], Role::READER],
            'writer -w w' => ['writer', [Role::WRITER], Role::WRITER],
            'writer rw r' => ['writer', [Role::READER, Role::WRITER], Role::READER],
            'writer rw w' => ['writer', [Role::READER, Role::WRITER], Role::WRITER],
        ];
    }
}
