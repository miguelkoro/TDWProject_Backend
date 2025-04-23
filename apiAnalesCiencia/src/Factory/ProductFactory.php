<?php

/**
 * src/Factory/ProductFactory.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Product;

class ProductFactory extends ElementFactory
{
    /**
     * Product builder.
     */
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Product {
        assert($name !== '');
        return new Product($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }
}
