<?php

/**
 * tests/Entity/UserTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Entity;

use Faker\Factory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use TDW\ACiencia\Entity\{ Role, User };

/**
 * Class UserTest
 */
#[TestsAttr\Group('users')]
#[TestsAttr\CoversClass(User::class)]
class UserTest extends TestCase
{
    protected static User $user;

    private static \Faker\Generator $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$user  = new User();
        self::$faker = Factory::create('es_ES');
    }

    /**
     * @return void
     */
    public function testConstructorOK(): void
    {
        self::$user = new User();
        static::assertSame(0, self::$user->getId());
        static::assertNotEmpty(self::$user->getUsername());
        static::assertEmpty(self::$user->getEmail());
        static::assertTrue(self::$user->validatePassword(''));
        static::assertTrue(self::$user->hasRole(Role::READER));
        static::assertFalse(self::$user->hasRole(Role::WRITER));
    }

    /**
     * @return void
     */
    public function testConstructorInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$user = new User(role: self::$faker->word());
    }

    public function testGetId(): void
    {
        static::assertSame(0, self::$user->getId());
    }

    #[TestsAttr\Depends('testConstructorOK')]
    public function testGetSetUsername(): void
    {
        static::assertNotEmpty(self::$user->getUsername());
        $username = self::$faker->userName();
        static::assertNotEmpty($username);
        self::$user->setUsername($username);
        static::assertSame($username, self::$user->getUsername());
    }

    public function testGetSetEmail(): void
    {
        $userEmail = self::$faker->email();
        static::assertEmpty(self::$user->getEmail());
        self::$user->setEmail($userEmail);
        static::assertSame($userEmail, self::$user->getEmail());
    }

    public function testRoles(): void
    {
        self::$user->setRole(Role::INACTIVE);
        static::assertTrue(self::$user->hasRole(Role::INACTIVE));
        static::assertFalse(self::$user->hasRole(Role::READER));
        static::assertFalse(self::$user->hasRole(Role::WRITER));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertTrue(in_array(Role::INACTIVE, $roles, true));
        static::assertFalse(in_array(Role::READER, $roles, true) === true);
        static::assertFalse(in_array(Role::WRITER, $roles, true) === true);

        self::$user->setRole(Role::READER);
        static::assertTrue(self::$user->hasRole(Role::READER));
        static::assertFalse(self::$user->hasRole(Role::INACTIVE));
        static::assertFalse(self::$user->hasRole(Role::WRITER));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertTrue(in_array(Role::READER, $roles, true));
        static::assertFalse(in_array(Role::INACTIVE, $roles, true));
        static::assertFalse(in_array(Role::WRITER, $roles, true));

        self::$user->setRole(Role::WRITER->value);
        static::assertTrue(self::$user->hasRole(Role::WRITER));
        static::assertTrue(self::$user->hasRole(Role::READER));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertFalse(in_array(Role::INACTIVE, $roles, true));
        static::assertTrue(in_array(Role::READER, $roles, true));
        static::assertTrue(in_array(Role::WRITER, $roles, true));
    }

    public function testRoleExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$user->setRole(self::$faker->word());
    }

    public function testGetSetValidatePassword(): void
    {
        $password = self::$faker->password();
        self::$user->setPassword($password);
        static::assertTrue(password_verify($password, self::$user->getPassword()));
        static::assertTrue(self::$user->validatePassword($password));
    }

    #[TestsAttr\Depends('testGetSetUsername')]
    public function testToString(): void
    {
        $username = self::$faker->userName();
        static::assertNotEmpty($username);
        self::$user->setUsername($username);
        static::assertStringContainsString($username, self::$user->__toString());
    }

    public function testJsonSerialize(): void
    {
        $json = (string) json_encode(self::$user, JSON_PARTIAL_OUTPUT_ON_ERROR);
        static::assertJson($json);
        $data = json_decode($json, true);
        static::assertArrayHasKey(
            'user',
            $data
        );
        static::assertArrayHasKey(
            'id',
            $data['user']
        );
        static::assertArrayHasKey(
            'username',
            $data['user']
        );
    }
}
