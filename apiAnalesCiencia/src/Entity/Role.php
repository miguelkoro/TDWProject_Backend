<?php

/**
 * src/Entity/Role.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

/**
 * @Enum({ "inactive", "reader", "writer" })
 */
enum Role: string
{
    // scope names (roles)
    case INACTIVE = 'inactive';
    case READER = 'reader';
    case WRITER = 'writer';

    public final const array ALL_VALUES = [
        Role::INACTIVE->value,
        Role::READER->value,
        Role::WRITER->value,
    ];

    public function is(Role $role): bool
    {
        return $this->value === $role->value;
    }
}
