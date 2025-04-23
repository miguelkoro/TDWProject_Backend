<?php

/**
 * src/Controller/Association/AssociationQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Association;

use TDW\ACiencia\Controller\Element\ElementBaseQueryController;
use TDW\ACiencia\Entity\Association;

/**
 * Class AssociationQueryController
 */
class AssociationQueryController extends ElementBaseQueryController
{
    /** @var string ruta api gestión Associations  */
    public const PATH_ASSOCIATIONS = '/associations';

    public static function getEntitiesTag(): string
    {
        return substr(self::PATH_ASSOCIATIONS, 1);
    }

    public static function getEntityClassName(): string
    {
        return Association::class;
    }

    public static function getEntityIdName(): string
    {
        return 'associationId';
    }
}
