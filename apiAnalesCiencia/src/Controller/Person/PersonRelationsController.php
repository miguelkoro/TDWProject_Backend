<?php

/**
 * src/Controller/Person/PersonRelationsController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Person;

use Doctrine\ORM;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Entity\EntityQueryController;
use TDW\ACiencia\Controller\Product\ProductQueryController;
use TDW\ACiencia\Entity\Person;

/**
 * Class PersonRelationsController
 */
final class PersonRelationsController extends ElementRelationsBaseController
{
    public static function getEntityClassName(): string
    {
        return PersonQueryController::getEntityClassName();
    }

    public static function getEntitiesTag(): string
    {
        return PersonQueryController::getEntitiesTag();
    }

    public static function getEntityIdName(): string
    {
        return PersonQueryController::getEntityIdName();
    }

    /**
     * Summary: GET /persons/{personId}/entities
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function getEntities(Request $request, Response $response, array $args): Response
    {
        // Person --> entities
        $personId = $args[PersonQueryController::getEntityIdName()] ?? 0;
        if ($personId <= 0 || $personId > 2147483647) {   // 404
            return $this->getElements($request, $response, null, EntityQueryController::getEntitiesTag(), []);
        }
        /** @var Person|null $person */
        $person = $this->entityManager
            ->getRepository(PersonQueryController::getEntityClassName())
            ->find($personId);

        $entities = $person?->getEntities()->getValues() ?? [];

        return $this->getElements($request, $response, $person, EntityQueryController::getEntitiesTag(), $entities);

        

    }

    /**
     * PUT /persons/{personId}/entities/add/{stuffId}
     * PUT /persons/{personId}/entities/rem/{stuffId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function operationEntity(Request $request, Response $response, array $args): Response
    {
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            EntityQueryController::getEntityClassName()
        );
    }

    /**
     * Summary: GET /persons/{personId}/products
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function getProducts(Request $request, Response $response, array $args): Response
    {
        // Person --> products
        $personId = $args[PersonQueryController::getEntityIdName()] ?? 0;
        if ($personId <= 0 || $personId > 2147483647) {   // 404
            return $this->getElements($request, $response, null, ProductQueryController::getEntitiesTag(), []);
        }
        /** @var Person|null $person */
        $person = $this->entityManager
            ->getRepository(PersonQueryController::getEntityClassName())
            ->find($personId);

        $products = $person?->getProducts()->getValues() ?? [];

        return $this->getElements($request, $response, $person, ProductQueryController::getEntitiesTag(), $products);
    }

    /**
     * PUT /persons/{personId}/products/add/{stuffId}
     * PUT /persons/{personId}/products/rem/{stuffId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function operationProduct(Request $request, Response $response, array $args): Response
    {
        return $this->operationRelatedElements(
            $request,
            $response,
            $args,
            ProductQueryController::getEntityClassName()
        );
    }
}
