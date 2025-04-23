<?php

/**
 * src/Controller/Entity/EntityQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Entity;

use TDW\ACiencia\Controller\Element\ElementBaseQueryController;
use TDW\ACiencia\Entity\Entity;

/**
 * Class EntityQueryController
 */
class EntityQueryController extends ElementBaseQueryController
{
    /** @var string ruta api gestión entidades  */
    public const PATH_ENTITIES = '/entities';

    public static function getEntitiesTag(): string
    {
        return substr(self::PATH_ENTITIES, 1);
    }

    public static function getEntityClassName(): string
    {
        return Entity::class;
    }

    public static function getEntityIdName(): string
    {
        return 'entityId';
    }
}
