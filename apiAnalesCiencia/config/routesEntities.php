<?php

/**
 * config/routesEntities.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\ACiencia\Controller\Entity\{ EntityCommandController, EntityQueryController, EntityRelationsController };
use TDW\ACiencia\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/entities
 * ############################################################
 * @param App $app
 */
return function (App $app) {

    $REGEX_ENTITY_ID = '/{entityId:[0-9]+}';
    $REGEX_ELEMENT_ID = '/{elementId:[0-9]+}';
    $REGEX_ELEMENT_NAME = '{name:[ a-zA-Z0-9()áéíóúÁÉÍÓÚñÑ %$\.+-]+}';
    $UNLIMITED_OPTIONAL_PARAMETERS = '/[{params:.*}]';

    // CGET|HEAD: Returns all entities
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES,
        EntityQueryController::class . ':cget'
    )->setName('readEntities');
    //    ->add(JwtMiddleware::class);

    // GET|HEAD: Returns a entity based on a single ID
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID,
        EntityQueryController::class . ':get'
    )->setName('readEntity');
    //    ->add(JwtMiddleware::class);

    // GET: Returns status code 204 if entity name exists
    $app->get(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . '/entityname/' . $REGEX_ELEMENT_NAME,
        EntityQueryController::class . ':getElementByName'
    )->setName('existsEntity');

    // DELETE: Deletes a entity
    $app->delete(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID,
        EntityCommandController::class . ':delete'
    )->setName('deleteEntity')
        ->add(JwtMiddleware::class);

    // OPTIONS: Provides the list of HTTP supported methods
    $app->options(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . '[' . $REGEX_ENTITY_ID . ']',
        EntityQueryController::class . ':options'
    )->setName('optionsEntity');

    // POST: Creates a new entity
    $app->post(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES,
        EntityCommandController::class . ':post'
    )->setName('createEntity')
        ->add(JwtMiddleware::class);

    // PUT: Updates a entity
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID,
        EntityCommandController::class . ':put'
    )->setName('updateEntity')
        ->add(JwtMiddleware::class);

    // RELATIONSHIPS
    // OPTIONS /entities/{personId}[/{params:.*}]
    $app->options(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID . $UNLIMITED_OPTIONAL_PARAMETERS,
        EntityRelationsController::class . ':optionsElements'
    )->setName('optionsEntitiesRelationships');

    // GET /entities/{entityId}/persons
    $app->get(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/persons',
        EntityRelationsController::class . ':getPersons'
    )->setName('readEntityPersons');
    //    ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/persons/add/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/persons/add' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationPerson'
    )->setName('tdw_entities_add_person')
        ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/persons/rem/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/persons/rem' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationPerson'
    )->setName('tdw_entities_rem_person')
        ->add(JwtMiddleware::class);

    // GET /entities/{entityId}/products
    $app->get(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/products',
        EntityRelationsController::class . ':getProducts'
    )->setName('readEntityProducts');
    //    ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/products/add/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID
            . '/products/add' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationProduct'
    )->setName('tdw_entities_add_product')
        ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/products/rem/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID
        . '/products/rem' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationProduct'
    )->setName('tdw_entities_rem_product')
        ->add(JwtMiddleware::class);
    
    // AÑADIDA ASSOCIACIONES

    // GET /entities/{entityId}/associations
    $app->get(
        $_ENV['RUTA_API'] . EntityQueryController::PATH_ENTITIES . $REGEX_ENTITY_ID . '/associations',
        EntityRelationsController::class . ':getAssociations'
    )->setName('readEntityAssociations');

    // PUT /entities/{entityId}/associations/add/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID 
            . '/associations/add' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationAssociation'
    )->setName('tdw_entities_add_association')
        ->add(JwtMiddleware::class);

    // PUT /entities/{entityId}/associations/rem/{elementId}
    $app->put(
        $_ENV['RUTA_API'] . EntityCommandController::PATH_ENTITIES . $REGEX_ENTITY_ID 
            . '/associations/rem' . $REGEX_ELEMENT_ID,
        EntityRelationsController::class . ':operationAssociation'
    )->setName('tdw_entities_rem_association')
        ->add(JwtMiddleware::class);
};
