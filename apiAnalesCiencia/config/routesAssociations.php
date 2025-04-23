<?php

/**
 * config/routesAssociations.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\ACiencia\Controller\Association\{ AssociationCommandController, AssociationQueryController, AssociationRelationsController };
use TDW\ACiencia\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/associations
 * ############################################################
 * @param App $app
 */
return function (App $app) {

    $REGEX_ASSOCIATION_ID = '/{associationId:[0-9]+}';
    $REGEX_ELEMENT_ID = '/{elementId:[0-9]+}';
    $REGEX_ELEMENT_NAME = '{name:[ a-zA-Z0-9()áéíóúÁÉÍÓÚñÑ %$\.+-]+}';
    $UNLIMITED_OPTIONAL_PARAMETERS = '/[{params:.*}]';

    // CGET|HEAD: Returns all associations
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS,
        AssociationQueryController::class . ':cget'
    )->setName('readAssociations');
    //    ->add(JwtMiddleware::class);

    // GET|HEAD: Returns a association based on a single ID
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID,
        AssociationQueryController::class . ':get'
    )->setName('readAssociation');
    //    ->add(JwtMiddleware::class);

    // GET: Returns status code 204 if association name exists
    $app->get(
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS . '/associationname/' . $REGEX_ELEMENT_NAME,
        AssociationQueryController::class . ':getElementByName'
    )->setName('existsAssociation');

    // DELETE: Deletes a association
    $app->delete(
        $_ENV['RUTA_API'] . AssociationCommandController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID,
        AssociationCommandController::class . ':delete'
    )->setName('deleteAssociation')
        ->add(JwtMiddleware::class);

    // OPTIONS: Provides the list of HTTP supported methods
    $app->options(
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS . '[' . $REGEX_ASSOCIATION_ID . ']',
        AssociationQueryController::class . ':options'
    )->setName('optionsAssociation');

    // POST: Creates a new association
    $app->post(
        $_ENV['RUTA_API'] . AssociationCommandController::PATH_ASSOCIATIONS,
        AssociationCommandController::class . ':post'
    )->setName('createAssociation')
        ->add(JwtMiddleware::class);

    // PUT: Updates a association
    $app->put(
        $_ENV['RUTA_API'] . AssociationCommandController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID,
        AssociationCommandController::class . ':put'
    )->setName('updateAssociation')
        ->add(JwtMiddleware::class);

    // RELATIONSHIPS
    // OPTIONS /associations/{entityId}[/{params:.*}]
    $app->options(
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID . $UNLIMITED_OPTIONAL_PARAMETERS,
        AssociationRelationsController::class . ':optionsElements'
    )->setName('optionsAssociationsRelationships');

    // GET /associations/{associationId}/entities
    $app->get(
        $_ENV['RUTA_API'] . AssociationQueryController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID . '/entities',
        AssociationRelationsController::class . ':getEntities'
    )->setName('readAssociationEntities');        
    //    ->add(JwtMiddleware::class);

    // PUT /associations/{associationId}/entities/add/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . AssociationCommandController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID . '/entities/add' . $REGEX_ELEMENT_ID,
        AssociationRelationsController::class . ':operationEntity'
    )->setName('tdw_associations_add_entity')
        ->add(JwtMiddleware::class);

    // PUT /associations/{associationId}/entities/rem/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . AssociationCommandController::PATH_ASSOCIATIONS . $REGEX_ASSOCIATION_ID . '/entities/rem' . $REGEX_ELEMENT_ID,
        AssociationRelationsController::class . ':operationEntity'
    )->setName('tdw_associations_rem_entity')
        ->add(JwtMiddleware::class);

    // GET /entities/{entityId}/products
   /* $app->get(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/products',
        EntityRelationsController::class . ':getProducts'
    )->setName('readEntityProducts');*/
    //    ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/products/add/{elementId}
    /*$app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID
            . '/products/add' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationProduct'
    )->setName('tdw_entities_add_product')
        ->add(JwtMiddleware::class);*/

    // PUT /entities/{entityId}/products/rem/{elementId}
   /* $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID
        . '/products/rem' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationProduct'
    )->setName('tdw_entities_rem_product')
        ->add(JwtMiddleware::class);*/
};
