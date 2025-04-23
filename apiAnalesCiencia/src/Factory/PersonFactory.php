<?php

/**
 * src/Factory/PersonFactory.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Person;

class PersonFactory extends ElementFactory
{
    /**
     * Person builder.
     */
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Person {
        assert($name !== '');
        return new Person($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }
}
