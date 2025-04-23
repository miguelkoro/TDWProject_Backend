<?php

/**
 * src/Controller/Association/AssociationCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Association;

use TDW\ACiencia\Controller\Element\ElementBaseCommandController;
use TDW\ACiencia\Entity\Association;
use TDW\ACiencia\Factory\AssociationFactory;

/**
 * Class AssociationCommandController
 */
class AssociationCommandController extends ElementBaseCommandController
{
    /** @var string ruta api gestión Associations  */
    public const PATH_ASSOCIATIONS = '/associations';

    public static function getEntityClassName(): string
    {
        return Association::class;
    }

    protected static function getFactoryClassName(): string
    {
        return AssociationFactory::class;
    }

    public static function getEntityIdName(): string
    {
        return 'associationId';
    }
}
