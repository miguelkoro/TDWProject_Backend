<?php

/**
 * src/scripts/remove.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Doctrine\ORM\EntityManager;
use TDW\ACiencia\Entity\{ Element, Entity, Person, Product };
use TDW\ACiencia\Utility\DoctrineConnector;

require __DIR__ . '/inicio.php';

if ($argc !== 3) {
    $texto = <<< ______USO

    *> Usage: {$argv[0]} [product | entity | person] <entityId>
    Deletes the element of type [product | entity | person] specified by <entityId>

______USO;
    die($texto);
}

$ElementType = strtolower($argv[1]);
$elementId = (int) $argv[2];
try {
    $elementClass = match ($ElementType) {
        'product' => Product::class,
        'entity' => Entity::class,
        'person' => Person::class,
        default => throw new ErrorException('Second parameter Element must be [product | entity | person]'),
    };

    /** @var EntityManager $entityManager */
    $entityManager = DoctrineConnector::getEntityManager();
    $element = $entityManager
        ->find($elementClass, $elementId);
    if (!$element instanceof Element) {
        exit('Element [' . $elementId . '] not exist.' . PHP_EOL);
    }
    $entityManager->remove($element);
    $entityManager->flush();
    printf(
        'Element with id=%d type %s removed',
        $elementId,
        $elementClass
    );
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}
