<?php

/**
 * tests/Auth/JwtAuthTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

declare(strict_types=1);

namespace TDW\Test\ACiencia\Auth;

use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use Selective\Config\Configuration;
use Selective\TestTrait\Traits\ContainerTestTrait;
use TDW\ACiencia\Auth\JwtAuth;
use TDW\ACiencia\Entity\User;
use Throwable;

#[TestsAttr\CoversClass(JwtAuth::class)]
class JwtAuthTest extends TestCase
{
    use ContainerTestTrait;

    protected JwtAuth $jwtAuth;
    protected Configuration $config;

    private const USERNAME = 'user12345';

    protected function setUp(): void
    {
        parent::setUp();
        try {
            $containerBuilder = new ContainerBuilder();
            $container = $containerBuilder
                ->addDefinitions(
                    __DIR__ .  '/../../config/container.php'
                )
                ->build();
            $this->setUpContainer($container);
            $this->config = $container->get(Configuration::class);
            $this->jwtAuth = $container->get(JwtAuth::class);
        } catch (Throwable $e) {
            die('code:' . $e->getCode() . ', msg=' . $e->getMessage());
        }
    }

    public function testGetLifetime(): void
    {
        self::assertSame(
            $this->config->getInt('jwt.lifetime'),
            $this->jwtAuth->getLifetime()
        );
    }

    /**
     * @return string jwt
     */
    public function testCreateJwt(): string
    {
        $user = new User(username: self::USERNAME);
        $plainJwt = $this->jwtAuth->createJwt($user);
        self::assertNotEmpty($this->config->getString('jwt.issuer'));
        self::assertTrue(
            $plainJwt->hasBeenIssuedBy(
                $this->config->getString('jwt.issuer')
            )
        );
        self::assertNotEmpty($this->config->getString('jwt.client-id'));
        self::assertTrue(
            $plainJwt->isPermittedFor(
                $this->config->getString('jwt.client-id')
            )
        );
        self::assertTrue(
            $plainJwt->isRelatedTo(self::USERNAME)
        );

        return $plainJwt->toString();
    }

    #[TestsAttr\Depends('testCreateJwt')]
    public function testCreateParsedToken(string $token): void
    {
        self::assertNotEmpty($token);
        self::assertNotEmpty($this->config->getString('jwt.issuer'));
        $parsedToken = $this->jwtAuth->createParsedToken($token);
        self::assertTrue(
            $parsedToken->hasBeenIssuedBy(
                $this->config->getString('jwt.issuer')
            )
        );
        self::assertNotEmpty($this->config->getString('jwt.client-id'));
        self::assertTrue(
            $parsedToken->isPermittedFor(
                $this->config->getString('jwt.client-id')
            )
        );
        self::assertTrue($parsedToken->isRelatedTo(self::USERNAME));
    }

    #[TestsAttr\Depends('testCreateJwt')]
    public function testValidateTokenOK(string $accessToken): void
    {
        self::assertNotEmpty($accessToken);
        self::assertTrue(
            $this->jwtAuth->validateToken($accessToken)
        );
    }

    #[TestsAttr\Depends('testCreateJwt')]
    public function testValidateTokenNotOk(string $accessToken): void
    {
        $this->expectException(Exception::class);
        $this->jwtAuth->validateToken($accessToken . 'x');
    }
}
