<?php

/**
 * src/Controller/User/TraitController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller;

use Lcobucci\JWT\Token\Plain;
use Psr\Http\Message\ServerRequestInterface as Request;
use TDW\ACiencia\Entity\Role;

trait TraitController
{
    /**
     * Get the userId from token request
     *
     * @param Request $request Representation of an incoming server-side HTTP request
     *
     * @return int User id (0 if the information is not available)
     */
    public function getUserId(Request $request): int
    {
        /** @var Plain|null $token */
        $token = $request->getAttribute('token');
        return (int) $token?->claims()->get('uid', 0);
    }

    /**
     * Check from token request if user is writer
     *
     * @param Request $request Representation of an incoming server-side HTTP request
     *
     * @return bool
     */
    public function checkWriterScope(Request $request): bool
    {
        /** @var Plain|null $token */
        $token = $request->getAttribute('token');
        $scopes = $token?->claims()->get('scopes');
        return is_array($scopes) && in_array(Role::WRITER->value, $scopes, true);
    }
}
