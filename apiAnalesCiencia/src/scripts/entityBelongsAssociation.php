<?php

/**
 * src/scripts/entityBelongsAssociation.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Doctrine\ORM\EntityManager;
use TDW\ACiencia\Entity\{ Association, Entity };
use TDW\ACiencia\Utility\DoctrineConnector;

require __DIR__ . '/inicio.php';

if (3 !== $argc) {
    $fich = basename(__FILE__);
    echo <<< MARCA_FIN

Usage: $fich <associationId> <entityId>
 
MARCA_FIN;
    exit(0);
}
//Entidad 👑  --> Asociación
$associationId = (int) $argv[1]; //person
$entityId = (int) $argv[2]; //Product

try {
    /** @var EntityManager $entityManager */
    $entityManager = DoctrineConnector::getEntityManager();
    /** @var Association|null $association */
    $association = $entityManager->find(Association::class, $associationId);
    if (!$association instanceof Association) {
        throw new Exception("Association $associationId not exist" . PHP_EOL);
    }
    /** @var Entity|null $entity */
    $entity = $entityManager->find(Entity::class, $entityId);
    if (!$entity instanceof Entity) {
        throw new Exception("Entity $entityId not exist" . PHP_EOL);
    }

    $association->addEntity($entity);
    $entityManager->flush();
    $entityManager->close();
    echo 'Association ID=' . $association->getId() . ': added entity ' . $entityId . PHP_EOL;
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}
