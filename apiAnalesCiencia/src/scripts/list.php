<?php

/**
 * src/scripts/list.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Doctrine\ORM\EntityManager;
use TDW\ACiencia\Entity\{ Entity, Person, Product, Association };
use TDW\ACiencia\Utility\DoctrineConnector;

require __DIR__ . '/inicio.php';

if ($argc < 2) {
    $texto = <<< ____USO

    *> Usage: {$argv[0]} (product | entity | person | association) [--json]
    Lists the elements of type [product | entity | person | association] specified

____USO;
    die($texto);
}

$ElementType = strtolower($argv[1]);

try {
    $elementClass = match ($ElementType) {
        'product' => Product::class,
        'entity' => Entity::class,
        'person' => Person::class,
        'association' => Association::class,
        default => throw new ErrorException('Second parameter Element must be [product | entity | person | association]'),
    };

    /** @var EntityManager $entityManager */
    $entityManager = DoctrineConnector::getEntityManager();
    $elements = $entityManager->getRepository($elementClass)->findAll();
    $entityManager->close();
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}

// Salida formato JSON
if (in_array('--json', $argv, true)) {
    echo json_encode(
        $elements,
        JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
    );
} else {
    foreach ($elements as $element) {
        echo $element . PHP_EOL;
    }
    echo sprintf("\nTotal: %d elements.\n\n", count($elements));
}
