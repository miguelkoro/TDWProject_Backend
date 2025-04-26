<?php

/**
 * tests/Entity/ProductTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Entity;

use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use TDW\ACiencia\Entity\{ Element, Association };
use TDW\ACiencia\Factory;

/**
 * Class ProductTest
 */
#[TestsAttr\Group('associations')]
#[TestsAttr\CoversClass(Association::class)]
#[TestsAttr\CoversClass(Element::class)]
#[TestsAttr\CoversClass(Factory\AssociationFactory::class)]
#[TestsAttr\UsesClass(Factory\EntityFactory::class)]
//#[TestsAttr\UsesClass(Factory\PersonFactory::class)]
class AssociationTest extends TestCase
{
    protected static Association $association;

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
        self::$association  = Factory\AssociationFactory::createElement($name);
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $name = self::$faker->name();
        self::assertNotEmpty($name);
        self::$association = Factory\AssociationFactory::createElement($name);
        self::assertSame(0, self::$association->getId());
        self::assertSame(
            $name,
            self::$association->getName()
        );
        self::assertEmpty(self::$association->getEntities());
        //self::assertEmpty(self::$product->getPersons());
    }

    public function testGetId(): void
    {
        self::assertSame(0, self::$association->getId());
    }

    public function testGetSetAssociationName(): void
    {
        $associationname = self::$faker->name();
        self::assertNotEmpty($associationname);
        self::$association->setName($associationname);
        static::assertSame(
            $associationname,
            self::$association->getName()
        );
    }

    public function testGetSetBirthDate(): void
    {
        $birthDate = self::$faker->dateTime();
        self::$association->setBirthDate($birthDate);
        static::assertSame(
            $birthDate,
            self::$association->getBirthDate()
        );
    }

    public function testGetSetDeathDate(): void
    {
        $deathDate = self::$faker->dateTime();
        self::$association->setDeathDate($deathDate);
        static::assertSame(
            $deathDate,
            self::$association->getDeathDate()
        );
    }

    public function testGetSetImageUrl(): void
    {
        $imageUrl = self::$faker->url();
        self::$association->setImageUrl($imageUrl);
        static::assertSame(
            $imageUrl,
            self::$association->getImageUrl()
        );
    }

    public function testGetSetWikiUrl(): void
    {
        $wikiUrl = self::$faker->url();
        self::$association->setWikiUrl($wikiUrl);
        static::assertSame(
            $wikiUrl,
            self::$association->getWikiUrl()
        );
    }

    public function testGetAddContainsRemoveEntities(): void
    {
        self::assertEmpty(self::$association->getEntities());
        $slug = self::$faker->slug();
        self::assertNotEmpty($slug);
        $entity = Factory\EntityFactory::createElement($slug);
        self::$association->addEntity($entity);
        self::$association->addEntity($entity); // CCoverage

        self::assertNotEmpty(self::$association->getEntities());
        self::assertTrue(self::$association->containsEntity($entity));

        self::$association->removeEntity($entity);
        self::assertFalse(self::$association->containsEntity($entity));
        self::assertCount(0, self::$association->getEntities());
        self::assertFalse(self::$association->removeEntity($entity));
    }

    /*public function testGetAddContainsRemovePersons(): void
    {
        self::assertEmpty(self::$product->getPersons());
        $slug = self::$faker->slug();
        self::assertNotEmpty($slug);
        $person = Factory\PersonFactory::createElement($slug);
        self::$product->addPerson($person);
        self::$product->addPerson($person);  // CCoverage

        self::assertNotEmpty(self::$product->getPersons());
        self::assertTrue(self::$product->containsPerson($person));

        self::$product->removePerson($person);
        self::assertFalse(self::$product->containsPerson($person));
        self::assertCount(0, self::$product->getPersons());
        self::assertFalse(self::$product->removePerson($person));
    }*/

    public function testToString(): void
    {
        $associationName = self::$faker->text();
        self::assertNotEmpty($associationName);
        $birthDate = self::$faker->dateTime();
        $deathDate = self::$faker->dateTime();
        self::$association->setName($associationName);
        self::$association->setBirthDate($birthDate);
        self::$association->setDeathDate($deathDate);
        self::assertStringContainsString(
            $associationName,
            self::$association->__toString()
        );
        self::assertStringContainsString(
            $birthDate->format('Y-m-d'),
            self::$association->__toString()
        );
        self::assertStringContainsString(
            $deathDate->format('Y-m-d'),
            self::$association->__toString()
        );
    }

    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$association, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertJson($jsonStr);
    }
}
