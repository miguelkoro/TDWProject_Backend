<?php

/**
 * tests/Entity/EntityTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Entity;

use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use TDW\ACiencia\Entity\{ Element, Entity };
use TDW\ACiencia\Factory;
use function PHPUnit\Framework\assertNotEmpty;

/**
 * Class EntityTest
 */
#[TestsAttr\Group('entities')]
#[TestsAttr\CoversClass(Entity::class)]
#[TestsAttr\CoversClass(Element::class)]
#[TestsAttr\CoversClass(Factory\EntityFactory::class)]
#[TestsAttr\UsesClass(Factory\PersonFactory::class)]
#[TestsAttr\UsesClass(Factory\ProductFactory::class)]
class EntityTest extends TestCase
{
    protected static Entity $entity;

    private static \Faker\Generator $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = \Faker\Factory::create('es_ES');
        $name = self::$faker->name();
        self::assertNotEmpty($name);
        self::$entity = Factory\EntityFactory::createElement($name);
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $name = self::$faker->name();
        self::assertNotEmpty($name);
        self::$entity = Factory\EntityFactory::createElement($name);
        self::assertSame(0, self::$entity->getId());
        self::assertSame(
            $name,
            self::$entity->getName()
        );
        self::assertEmpty(self::$entity->getProducts());
        self::assertEmpty(self::$entity->getPersons());
    }

    public function testGetId(): void
    {
        self::assertSame(0, self::$entity->getId());
    }

    public function testGetSetEntityName(): void
    {
        /** @var non-empty-string $entityname */
        $entityname = self::$faker->name();
        self::$entity->setName($entityname);
        static::assertSame(
            $entityname,
            self::$entity->getName()
        );
    }

    public function testGetSetBirthDate(): void
    {
        $birthDate = self::$faker->dateTime();
        self::$entity->setBirthDate($birthDate);
        static::assertSame(
            $birthDate,
            self::$entity->getBirthDate()
        );
    }

    public function testGetSetDeathDate(): void
    {
        $deathDate = self::$faker->dateTime();
        self::$entity->setDeathDate($deathDate);
        static::assertSame(
            $deathDate,
            self::$entity->getDeathDate()
        );
    }

    public function testGetSetImageUrl(): void
    {
        $imageUrl = self::$faker->url();
        self::$entity->setImageUrl($imageUrl);
        static::assertSame(
            $imageUrl,
            self::$entity->getImageUrl()
        );
    }

    public function testGetSetWikiUrl(): void
    {
        $wikiUrl = self::$faker->url();
        self::$entity->setWikiUrl($wikiUrl);
        static::assertSame(
            $wikiUrl,
            self::$entity->getWikiUrl()
        );
    }

    public function testGetAddContainsRemovePersons(): void
    {
        self::assertEmpty(self::$entity->getPersons());
        $slug = self::$faker->slug();
        self::assertNotEmpty($slug);
        $person = Factory\PersonFactory::createElement($slug);

        self::$entity->addPerson($person);
        self::$entity->addPerson($person);  // CC
        self::assertNotEmpty(self::$entity->getPersons());
        self::assertTrue(self::$entity->containsPerson($person));

        self::$entity->removePerson($person);
        self::assertFalse(self::$entity->containsPerson($person));
        self::assertCount(0, self::$entity->getPersons());
        self::assertFalse(self::$entity->removePerson($person));
    }

    public function testGetAddContainsRemoveProducts(): void
    {
        self::assertEmpty(self::$entity->getProducts());
        $slug = self::$faker->slug();
        self::assertNotEmpty($slug);
        $product = Factory\ProductFactory::createElement($slug);

        self::$entity->addProduct($product);
        self::assertNotEmpty(self::$entity->getProducts());
        self::assertTrue(self::$entity->containsProduct($product));
        self::assertTrue($product->containsEntity(self::$entity));

        self::$entity->removeProduct($product);
        self::assertFalse(self::$entity->containsProduct($product));
        self::assertCount(0, self::$entity->getProducts());
        self::assertFalse(self::$entity->removeProduct($product));
        self::assertFalse($product->containsEntity(self::$entity));
    }

    public function testToString(): void
    {
        /** @var non-empty-string $entityName */
        $entityName = self::$faker->company();
        $birthDate = self::$faker->dateTime();
        $deathDate = self::$faker->dateTime();
        self::$entity->setBirthDate($birthDate);
        self::$entity->setDeathDate($deathDate);
        self::$entity->setName($entityName);
        self::assertStringContainsString(
            $entityName,
            self::$entity->__toString()
        );
        self::assertStringContainsString(
            $birthDate->format('Y-m-d'),
            self::$entity->__toString()
        );
        self::assertStringContainsString(
            $deathDate->format('Y-m-d'),
            self::$entity->__toString()
        );
    }

    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$entity, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertJson($jsonStr);
    }
}
