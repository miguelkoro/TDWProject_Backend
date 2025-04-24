<?php

/**
 * src/Controller/Association/AssociationRelationsController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Association;

use Doctrine\ORM;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Entity\EntityQueryController;
//use TDW\ACiencia\Controller\Product\ProductQueryController;
use TDW\ACiencia\Entity\Association;

/**
 * Class AssociationRelationsController
 */
final class AssociationRelationsController extends ElementRelationsBaseController
{
    public static function getEntityClassName(): string
    {
        return AssociationQueryController::getEntityClassName();
    }

    public static function getEntitiesTag(): string
    {
        return AssociationQueryController::getEntitiesTag();
    }

    public static function getEntityIdName(): string
    {
        return AssociationQueryController::getEntityIdName();
    }

    /**
     * Summary: GET /associations/{associationId}/entities
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function getEntities(Request $request, Response $response, array $args): Response
    {
        // association -> entity
        $associationId = $args[AssociationQueryController::getEntityIdName()] ?? 0;
        if ($associationId <= 0 || $associationId > 2147483647) {   // 404
            return $this->getElements($request, $response, null, EntityQueryController::getEntitiesTag(), []);
        }
        /** @var Association|null $association */
        $association = $this->entityManager
            ->getRepository(AssociationQueryController::getEntityClassName())
            ->find($associationId);
        
        $entities = $association?->getEntities()->getValues() ?? [];

        return $this->getElements($request, $response, $association, EntityQueryController::getEntitiesTag(), $entities);
    }

    /**
     * PUT /associations/{associationId}/entities/add/{elementId}
     * PUT /associations/{associationId}/entities/rem/{elementId}
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
     * Summary: GET /entities/{entityId}/products
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
   /* public function getProducts(Request $request, Response $response, array $args): Response
    {
        // @TODO
    }*/

    /**
     * PUT /entities/{entityId}/products/add/{elementId}
     * PUT /entities/{entityId}/products/rem/{elementId}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
  /*  public function operationProduct(Request $request, Response $response, array $args): Response
    {
        // @TODO
    }*/
}
