<?php

/**
 * src/Controller/Product/ProductCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\Product;

use TDW\ACiencia\Controller\Element\ElementBaseCommandController;
use TDW\ACiencia\Entity\Product;
use TDW\ACiencia\Factory\ProductFactory;

/**
 * Class ProductCommandController
 */
class ProductCommandController extends ElementBaseCommandController
{
    /** @var string ruta api gestión productos  */
    public const PATH_PRODUCTS = '/products';

    public static function getEntityClassName(): string
    {
        return Product::class;
    }

    protected static function getFactoryClassName(): string
    {
        return ProductFactory::class;
    }

    public static function getEntityIdName(): string
    {
        return 'productId';
    }
}
