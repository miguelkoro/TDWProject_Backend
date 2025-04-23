<?php

/**
 * src/Factory/EntityFactory.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Entity;

class EntityFactory extends ElementFactory
{
    /**
     * Entity builder.
     */
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Entity {
        assert($name !== '');
        return new Entity($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }
}
