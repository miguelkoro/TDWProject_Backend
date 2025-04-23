<?php

/**
 * src/Entity/AssociationFactory.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Factory;

use DateTime;
use TDW\ACiencia\Entity\Association;

class AssociationFactory extends ElementFactory
{
    /**
     * Association builder.
     */
    public static function createElement(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ): Association {
        assert($name !== '');
        return new Association($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
    }
}
