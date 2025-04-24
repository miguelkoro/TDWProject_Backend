<?php

/**
 * src/scripts/create.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use TDW\ACiencia\Entity\Element;
use TDW\ACiencia\Factory\{ EntityFactory, PersonFactory, ProductFactory, AssociationFactory };
use TDW\ACiencia\Utility\DoctrineConnector;

require __DIR__ . '/inicio.php';

if (3 !== $argc) {
    $fich = basename(__FILE__);
    echo <<< MARCA_FIN

Usage: $fich [product | entity | person | association] <name>
 
MARCA_FIN;
    exit(0);
}

$ElementType = strtolower($argv[1]);
$name = $argv[2];
assert($name !== '');

try {
    /** @var class-string $factoryClass */
    $factoryClass = match ($ElementType) {
        'product' => ProductFactory::class,
        'entity' => EntityFactory::class,
        'person' => PersonFactory::class,
        'association' => AssociationFactory::class,
        default => throw new ErrorException('Second parameter Element must be [product | entity | person | association]'),
    };

    $entityManager = DoctrineConnector::getEntityManager();
    /** @var Element $element */
    $element = $factoryClass::createElement($name);
    $entity = $entityManager->getRepository($element::class)->findOneBy(['name' => $name]);
    if (null !== $entity) {
        throw new Exception("Element $name of type " . $element::class . " already exists" . PHP_EOL);
    }

    $entityManager->persist($element);
    $entityManager->flush();
    echo 'Created Element with ID ' . $element->getId() . PHP_EOL;

    $entityManager->close();
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}
