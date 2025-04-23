<?php

/**
 * src/Controller/Product/ProductQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Product;

use TDW\ACiencia\Controller\Element\ElementBaseQueryController;
use TDW\ACiencia\Entity\Product;

/**
 * Class ProductQueryController
 */
class ProductQueryController extends ElementBaseQueryController
{
    /** @var string ruta api gestión productos  */
    public const PATH_PRODUCTS = '/products';

    public static function getEntitiesTag(): string
    {
        return substr(self::PATH_PRODUCTS, 1);
    }

    public static function getEntityClassName(): string
    {
        return Product::class;
    }

    public static function getEntityIdName(): string
    {
        return 'productId';
    }
}
