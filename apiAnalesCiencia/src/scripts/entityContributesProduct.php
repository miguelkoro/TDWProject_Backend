<?php

/**
 * src/scripts/entityContributesProduct.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Doctrine\ORM\EntityManager;
use TDW\ACiencia\Entity\{ Entity, Product };
use TDW\ACiencia\Utility\DoctrineConnector;

require __DIR__ . '/inicio.php';

if (3 !== $argc) {
    $fich = basename(__FILE__);
    echo <<< MARCA_FIN

Usage: $fich <entityId> <productId>
 
MARCA_FIN;
    exit(0);
}

$entityId = (int) $argv[1];
$productId = (int) $argv[2];

try {
    /** @var EntityManager $entityManager */
    $entityManager = DoctrineConnector::getEntityManager();
    /** @var Entity|null $entity */
    $entity = $entityManager->find(Entity::class, $entityId);
    if (!$entity instanceof Entity) {
        throw new Exception("Entity $entityId not exist" . PHP_EOL);
    }
    /** @var Product|null $product */
    $product = $entityManager->find(Product::class, $productId);
    if (!$product instanceof Product) {
        throw new Exception("Product $productId not exist" . PHP_EOL);
    }

    $entity->addProduct($product);
    $entityManager->flush();
    $entityManager->close();
    echo 'Entity ID=' . $entity->getId() . ': added product ' . $productId . PHP_EOL;
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}
