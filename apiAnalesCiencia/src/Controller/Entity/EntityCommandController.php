<?php

/**
 * src/Controller/Entity/EntityCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Entity;

use TDW\ACiencia\Controller\Element\ElementBaseCommandController;
use TDW\ACiencia\Entity\Entity;
use TDW\ACiencia\Factory\EntityFactory;

/**
 * Class EntityCommandController
 */
class EntityCommandController extends ElementBaseCommandController
{
    /** @var string ruta api gestión entityas  */
    public const PATH_ENTITIES = '/entities';

    public static function getEntityClassName(): string
    {
        return Entity::class;
    }

    protected static function getFactoryClassName(): string
    {
        return EntityFactory::class;
    }

    public static function getEntityIdName(): string
    {
        return 'entityId';
    }
}
