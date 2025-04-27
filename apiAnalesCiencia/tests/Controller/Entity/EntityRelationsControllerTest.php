<?php

/**
 * tests/Controller/Entity/EntityRelationsControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Entity;

use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Entity\{ EntityQueryController, EntityRelationsController };
use TDW\ACiencia\Entity\{ Entity, Person, Product, Association };
use TDW\ACiencia\Factory\{ EntityFactory, PersonFactory, ProductFactory, AssociationFactory };
use TDW\ACiencia\Utility\{ DoctrineConnector, Utils };
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class EntityRelationsControllerTest
 */
#[TestsAttr\CoversClass(EntityRelationsController::class)]
#[TestsAttr\CoversClass(ElementRelationsBaseController::class)]
final class EntityRelationsControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de entityas */
    protected const RUTA_API = '/api/v1/entities';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    protected static ?EntityManagerInterface $entityManager;

    private static Entity $entity;
    private static Person $person;
    private static Product $product;
    private static Association $association;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase UserControllerTest
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // load user admin fixtures
        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        self::$writer['id'] = Utils::loadUserData(
            username: (string) self::$writer['username'],
            email: (string) self::$writer['email'],
            password: (string) self::$writer['password'],
            isWriter: true
        );

        // load user reader fixtures
        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$reader['id'] = Utils::loadUserData(
            username: self::$reader['username'],
            email: self::$reader['email'],
            password: self::$reader['password'],
            isWriter: false
        );

        // create and insert fixtures
        $entityName = substr(self::$faker->company(), 0, 80);
        self::assertNotEmpty($entityName);
        self::$entity  = EntityFactory::createElement($entityName);

        $personName = substr(self::$faker->name(), 0, 80);
        self::assertNotEmpty($personName);
        self::$person  = PersonFactory::createElement($personName);

        $productName = substr(self::$faker->slug(), 0, 80);
        self::assertNotEmpty($productName);
        self::$product  = ProductFactory::createElement($productName);

        $associationName = substr(self::$faker->name(), 0, 80); #associacion
        self::assertNotEmpty($associationName);
        self::$association  = AssociationFactory::createElement($associationName);

        self::$entityManager = DoctrineConnector::getEntityManager();
        self::$entityManager->persist(self::$entity);
        self::$entityManager->persist(self::$person);
        self::$entityManager->persist(self::$product);
        self::$entityManager->persist(self::$association); # Association
        self::$entityManager->flush();
    }

    public function testGetEntitiesTag(): void
    {
        self::assertSame(
            EntityQueryController::getEntitiesTag(),
            EntityRelationsController::getEntitiesTag()
        );
    }

    // *******************
    // Entity -> Persons
    // *******************
    /**
     * OPTIONS /entities/{entityId}/persons
     * OPTIONS /entities/{entityId}/persons/add/{stuffId}
     */
    public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$entity->getId() . '/persons'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/persons/add/' . self::$person->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * PUT /entities/{entityId}/persons/add/{stuffId}
     */
    public function testAddPerson209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
                . '/persons/add/' . self::$person->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /entities/{entityId}/persons 200 Ok
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddPerson209')]
    public function testGetPersons200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$entity->getId() . '/persons',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responsePersons = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responsePersons);
        self::assertSame(
            self::$person->getName(),
            $responsePersons['persons'][0]['person']['name']
        );
    }

    /**
     * PUT /entities/{entityId}/persons/rem/{stuffId}
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetPersons200OkWithElements')]
    public function testRemovePerson209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/persons/rem/' . self::$person->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntity = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responseEntity['entity']);
        self::assertEmpty($responseEntity['entity']['persons']);
    }

    /**
     * GET /entities/{entityId}/persons 200 Ok - Empty
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemovePerson209')]
    public function testGetPersons200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$entity->getId() . '/persons',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responsePersons = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('persons', $responsePersons);
        self::assertEmpty($responsePersons['persons']);
    }

    // ******************
    // Entity -> Products
    // ******************
    /**
     * PUT /entities/{entityId}/products/add/{stuffId}
     */
    public function testAddProduct209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/products/add/' . self::$product->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson((string) $response->getBody());
    }

    /**
     * GET /entities/{entityId}/products 200 Ok
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddProduct209')]
    public function testGetProducts200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$entity->getId() . '/products',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProducts = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $responseProducts);
        self::assertSame(
            self::$product->getName(),
            $responseProducts['products'][0]['product']['name']
        );
    }

    /**
     * PUT /entities/{entityId}/products/rem/{stuffId}
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetProducts200OkWithElements')]
    public function testRemoveProduct209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/products/rem/' . self::$product->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $responseEntities['entity']);
        self::assertEmpty($responseEntities['entity']['products']);
    }

    /**
     * GET /entities/{entityId}/products 200 Ok - Empty
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemoveProduct209')]
    public function testGetProducts200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$entity->getId() . '/products',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProducts = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $responseProducts);
        self::assertEmpty($responseProducts['products']);
    }

    // *******************
    // Entity -> Associations
    // *******************
    /**
     * OPTIONS /entities/{entityId}/associations
     * OPTIONS /entities/{entityId}/associations/add/{stuffId}
     */
    /*public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$entity->getId() . '/associations'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/associations/add/' . self::$association->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }*/

    /**
     * PUT /entities/{entityId}/associations/add/{stuffId}
     */
    public function testAddAssociation209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
                . '/associations/add/' . self::$association->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /entities/{entityId}/associations 200 Ok
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddAssociation209')]
    public function testGetAssociations200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$entity->getId() . '/associations',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseAssociations = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('associations', $responseAssociations);
        self::assertSame(
            self::$association->getName(),
            $responseAssociations['associations'][0]['association']['name']
        );
    }

    /**
     * PUT /entities/{entityId}/associations/rem/{stuffId}
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetAssociations200OkWithElements')]
    public function testRemoveAssociation209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$entity->getId()
            . '/associations/rem/' . self::$association->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntity = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('associations', $responseEntity['entity']);
        self::assertEmpty($responseEntity['entity']['associations']);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param int $status
     * @param string $user
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeExceptionProvider')]
    public function testEntityRelationshipErrors(string $method, string $uri, int $status, string $user = ''): void
    {
        $requestingUser = match ($user) {
            'admin'  => self::$writer,
            'reader' => self::$reader,
            default  => ['username' => '', 'password' => '']
        };

        $response = $this->runApp(
            $method,
            $uri,
            null,
            $this->getTokenHeaders($requestingUser['username'], $requestingUser['password'])
        );
        $this->internalTestError($response, $status);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method, url, path, status, user ]
     */
    public static function routeExceptionProvider(): array
    {
        return [
            // 401
            // 'getPersons401'       => [ 'GET', self::RUTA_API . '/1/persons',       401],
            'putAddPerson401'     => [ 'PUT', self::RUTA_API . '/1/persons/add/1', 401],
            'putRemovePerson401'  => [ 'PUT', self::RUTA_API . '/1/persons/rem/1', 401],
            // 'getProducts401'      => [ 'GET', self::RUTA_API . '/1/products',        401],
            'putAddProduct401'    => [ 'PUT', self::RUTA_API . '/1/products/add/1',  401],
            'putRemoveProduct401' => [ 'PUT', self::RUTA_API . '/1/products/rem/1',  401],
            // 'getAssociations401'       => [ 'GET', self::RUTA_API . '/1/persons',       401],
            'putAddAssociation401'     => [ 'PUT', self::RUTA_API . '/1/associations/add/1', 401],
            'putRemoveAssociation401'  => [ 'PUT', self::RUTA_API . '/1/associations/rem/1', 401],

            // 403
            'putAddPerson403'     => [ 'PUT', self::RUTA_API . '/1/persons/add/1', 403, 'reader'],
            'putRemovePerson403'  => [ 'PUT', self::RUTA_API . '/1/persons/rem/1', 403, 'reader'],
            'putAddProduct403'    => [ 'PUT', self::RUTA_API . '/1/products/add/1',  403, 'reader'],
            'putRemoveProduct403' => [ 'PUT', self::RUTA_API . '/1/products/rem/1',  403, 'reader'],
            'putAddAssociation403'     => [ 'PUT', self::RUTA_API . '/1/associations/add/1', 403, 'reader'],
            'putRemoveAssociation403'  => [ 'PUT', self::RUTA_API . '/1/associations/rem/1', 403, 'reader'],

            // 404
            'getPersons404'       => [ 'GET', self::RUTA_API . '/0/persons',       404, 'admin'],
            'putAddPerson404'     => [ 'PUT', self::RUTA_API . '/0/persons/add/1', 404, 'admin'],
            'putRemovePerson404'  => [ 'PUT', self::RUTA_API . '/0/persons/rem/1', 404, 'admin'],
            'getProducts404'      => [ 'GET', self::RUTA_API . '/0/products',        404, 'admin'],
            'putAddProduct404'    => [ 'PUT', self::RUTA_API . '/0/products/add/1',  404, 'admin'],
            'putRemoveProduct404' => [ 'PUT', self::RUTA_API . '/0/products/rem/1',  404, 'admin'],
            'getAssociations404'       => [ 'GET', self::RUTA_API . '/0/associations',       404, 'admin'],
            'putAddAssociation404'     => [ 'PUT', self::RUTA_API . '/0/associations/add/1', 404, 'admin'],
            'putRemoveAssociation404'  => [ 'PUT', self::RUTA_API . '/0/associations/rem/1', 404, 'admin'],

            // 406
            'putAddPerson406'     => [ 'PUT', self::RUTA_API . '/1/persons/add/100', 406, 'admin'],
            'putRemovePerson406'  => [ 'PUT', self::RUTA_API . '/1/persons/rem/100', 406, 'admin'],
            'putAddProduct406'    => [ 'PUT', self::RUTA_API . '/1/products/add/100',  406, 'admin'],
            'putRemoveProduct406' => [ 'PUT', self::RUTA_API . '/1/products/rem/100',  406, 'admin'],
            'putAddAssociation406'     => [ 'PUT', self::RUTA_API . '/1/associations/add/100', 406, 'admin'],
            'putRemoveAssociation406'  => [ 'PUT', self::RUTA_API . '/1/associations/rem/100', 406, 'admin'],
        ];
    }
}
