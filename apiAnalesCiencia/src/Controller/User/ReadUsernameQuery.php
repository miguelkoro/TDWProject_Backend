<?php

/**
 * src/Controller/User/ReadUsernameQuery.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\User;

use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Entity\User;
use TDW\ACiencia\Utility\Error;

class ReadUsernameQuery
{
    // constructor receives container instance
    public function __construct(protected ORM\EntityManager $entityManager)
    {
    }

    /**
     * GET /api/v1/users/{username}
     *
     * Summary: Returns status code 204 if _username_ exists (or 404 if not)
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert(in_array($request->getMethod(), [ 'GET', 'HEAD' ], true));
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([ 'username' => $args['username'] ]);

        return ($user instanceof User)
            ? $response->withStatus(StatusCode::STATUS_NO_CONTENT)       // 204
            : Error::createResponse($response, StatusCode::STATUS_NOT_FOUND); // 404
    }
}
