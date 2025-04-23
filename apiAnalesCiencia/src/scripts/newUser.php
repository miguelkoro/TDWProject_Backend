<?php

/**
 * src/scripts/newUser.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

require __DIR__ . '/inicio.php';

use Doctrine\ORM\EntityManager;
use TDW\ACiencia\Entity\{ Role, User };
use TDW\ACiencia\Utility\DoctrineConnector;

try {
    $num = random_int(0, 100000);
    $role = ($num % 2 !== 0) ? Role::READER : Role::WRITER;
    $nombre = 'user-' . $num;

    /** @var EntityManager $entityManager */
    $entityManager = DoctrineConnector::getEntityManager();
    $user = new User($nombre, $nombre . '@example.com', $nombre, $role);

    $entityManager->persist($user);
    $entityManager->flush();
    echo 'Created User with ID ' . $user->getId() . PHP_EOL;
} catch (Throwable $e) {
    exit('ERROR (' . $e->getCode() . '): ' . $e->getMessage());
}
