<?php

/**
 * src/Controller/User/ReadAllQuery.php
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

/**
 * Class ReadAllQuery
 */
class ReadAllQuery
{
    /** @var string ruta api gestión usuarios  */
    public const PATH_USERS = '/users';

    // constructor receives container instance
    public function __construct(protected ORM\EntityManager $entityManager)
    {
    }

    /**
     * GET /api/v1/users
     *
     * Summary: Returns all users
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response): Response
    {
        assert(in_array($request->getMethod(), [ 'GET', 'HEAD' ], true));
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findAll();

        // @codeCoverageIgnoreStart
        if (0 === count($users)) {    // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        // @codeCoverageIgnoreEnd

        // Caching with ETag
        $etag = md5((string) json_encode($users));
        if (in_array($etag, $request->getHeader('If-None-Match'), true)) {
                return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED); // 304
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson([ 'users' => $users ]);
    }
}
