<?php

/**
 * src/Controller/Person/PersonQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Person;

use TDW\ACiencia\Controller\Element\ElementBaseQueryController;
use TDW\ACiencia\Entity\Person;

/**
 * Class PersonQueryController
 */
class PersonQueryController extends ElementBaseQueryController
{
    /** @var string ruta api gestión personas  */
    public const PATH_PERSONS = '/persons';

    public static function getEntitiesTag(): string
    {
        return substr(self::PATH_PERSONS, 1);
    }

    public static function getEntityClassName(): string
    {
        return Person::class;
    }

    public static function getEntityIdName(): string
    {
        return 'personId';
    }
}
