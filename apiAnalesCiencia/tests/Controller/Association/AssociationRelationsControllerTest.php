<?php

/**
 * tests/Controller/Association/AssociationRelationsControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Association;

use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Association\{ AssociationQueryController, AssociationRelationsController };
use TDW\ACiencia\Entity\{ Entity, Association};
use TDW\ACiencia\Factory\{ EntityFactory, AssociationFactory };
use TDW\ACiencia\Utility\{ DoctrineConnector, Utils };
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class AssociationRelationsControllerTest
 */
#[TestsAttr\CoversClass(AssociationRelationsController::class)]
#[TestsAttr\CoversClass(ElementRelationsBaseController::class)]
final class AssociationRelationsControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de associaciones */
    protected const RUTA_API = '/api/v1/associations';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    protected static ?EntityManagerInterface $entityManager;

    private static Association $association;
    private static Entity $entity;
    //private static Product $product;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase UserControllerTest
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        // load user admin fixtures
        self::$writer['id'] = Utils::loadUserData(
            self::$writer['username'],
            self::$writer['email'],
            self::$writer['password'],
            true
        );

        // load user reader fixtures
        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$reader['id'] = Utils::loadUserData(
            (string) self::$reader['username'],
            (string) self::$reader['email'],
            (string) self::$reader['password'],
            false
        );

        // create and insert fixtures
        $entityName = self::$faker->company();
        self::assertNotEmpty($entityName);
        self::$entity  = EntityFactory::createElement($entityName);

        $associationName = self::$faker->name();
        self::assertNotEmpty($associationName);
        self::$association  = AssociationFactory::createElement($associationName);

        /*$productName = self::$faker->slug();
        self::assertNotEmpty($productName);
        self::$product  = ProductFactory::createElement($productName);*/

        self::$entityManager = DoctrineConnector::getEntityManager();
        self::$entityManager->persist(self::$association);
        self::$entityManager->persist(self::$entity);
        //self::$entityManager->persist(self::$product);
        self::$entityManager->flush();
    }

    public function testGetEntitiesTag(): void
    {
        self::assertSame(
            AssociationQueryController::getEntitiesTag(),
            AssociationRelationsController::getEntitiesTag()
        );
    }

    // *******************
    // Associations -> Entities
    // *******************
    /**
     * OPTIONS /associations/{associationId}/entities
     * OPTIONS /associations/{associationId}/entities/add/{stuffId}
     */
    public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$association->getId() . '/entities'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$association->getId()
            . '/entities/add/' . self::$entity->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * PUT /associations/{associationId}/entities/add/{stuffId}
     */
    public function testAddEntity209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$association->getId()
                . '/entities/add/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /associations/{associationId}/entities 200 Ok
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddEntity209')]
    public function testGetEntities200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$association->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertSame(
            self::$entity->getName(),
            $responseEntities['entities'][0]['entity']['name']
        );
    }

    /**
     * PUT /associations/{associationId}/entities/rem/{stuffId}
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetEntities200OkWithElements')]
    public function testRemoveEntity209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$association->getId()
            . '/entities/rem/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseAssociation = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseAssociation['association']);
        self::assertEmpty($responseAssociation['association']['entities']);
    }

    /**
     * GET /associations/{associationId}/entities 200 Ok - Empty
     *
     * @return void
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemoveEntity209')]
    public function testGetEntities200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$association->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertEmpty($responseEntities['entities']);
    }

    // ******************
    // Person -> Products
    // ******************
    /**
     * PUT /persons/{personId}/products/add/{stuffId}
     */
   /* public function testAddProduct209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$person->getId()
            . '/products/add/' . self::$product->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }*/

    /**
     * GET /persons/{personId}/products 200 Ok
     *
     * @return void
     * @throws JsonException
     */
   /* #[TestsAttr\Depends('testAddProduct209')]
    public function testGetProducts200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$person->getId() . '/products',
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
    }*/

    /**
     * PUT /persons/{personId}/products/rem/{stuffId}
     *
     * @return void
     * @throws JsonException
     */
   /* #[TestsAttr\Depends('testGetProducts200OkWithElements')]
    public function testRemoveProduct209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$person->getId()
            . '/products/rem/' . self::$product->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responsePerson = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $responsePerson['person']);
        self::assertEmpty($responsePerson['person']['products']);
    }*/

    /**
     * GET /persons/{personId}/products 200 Ok - Empty
     *
     * @return void
     * @throws JsonException
     */
   /* #[TestsAttr\Depends('testRemoveProduct209')]
    public function testGetProducts200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$person->getId() . '/products',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseProducts = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('products', $responseProducts);
        self::assertEmpty($responseProducts['products']);
    }*/

    /**
     * @param string $method
     * @param string $uri
     * @param int $status
     * @param string $user
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeExceptionProvider')]
    public function testAssociationRelationshipErrors(string $method, string $uri, int $status, string $user = ''): void
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
     * @return array<string,mixed> [ method, url, path, status ]
     */
    public static function routeExceptionProvider(): array
    {
        return [
            // 401
            // 'getEntities401'     => [ 'GET', self::RUTA_API . '/1/entities',       401],
            'putAddEntity401'    => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 401],
            'putRemoveEntity401' => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 401],
            // 'getProducts401'      => [ 'GET', self::RUTA_API . '/1/products',        401],
            //'putAddProduct401'    => [ 'PUT', self::RUTA_API . '/1/products/add/1',  401],
            //'putRemoveProduct401' => [ 'PUT', self::RUTA_API . '/1/products/rem/1',  401],

            // 403
            'putAddEntity403'    => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 403, 'reader'],
            'putRemoveEntity403' => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 403, 'reader'],
            //'putAddProduct403'    => [ 'PUT', self::RUTA_API . '/1/products/add/1',  403, 'reader'],
            //'putRemoveProduct403' => [ 'PUT', self::RUTA_API . '/1/products/rem/1',  403, 'reader'],

            // 404
            'getEntities404'     => [ 'GET', self::RUTA_API . '/0/entities',       404, 'admin'],
            'putAddEntity404'    => [ 'PUT', self::RUTA_API . '/0/entities/add/1', 404, 'admin'],
            'putRemoveEntity404' => [ 'PUT', self::RUTA_API . '/0/entities/rem/1', 404, 'admin'],
            //'getProducts404'      => [ 'GET', self::RUTA_API . '/0/products',        404, 'admin'],
            //'putAddProduct404'    => [ 'PUT', self::RUTA_API . '/0/products/add/1',  404, 'admin'],
            //'putRemoveProduct404' => [ 'PUT', self::RUTA_API . '/0/products/rem/1',  404, 'admin'],

            // 406
            'putAddEntity406'    => [ 'PUT', self::RUTA_API . '/1/entities/add/100', 406, 'admin'],
            'putRemoveEntity406' => [ 'PUT', self::RUTA_API . '/1/entities/rem/100', 406, 'admin'],
            //'putAddProduct406'    => [ 'PUT', self::RUTA_API . '/1/products/add/100',  406, 'admin'],
            //'putRemoveProduct406' => [ 'PUT', self::RUTA_API . '/1/products/rem/100',  406, 'admin'],
        ];
    }
}
