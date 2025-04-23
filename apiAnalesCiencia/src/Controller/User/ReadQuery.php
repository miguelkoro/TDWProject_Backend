<?php

/**
 * src/Controller/User/ReadQuery.php
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

class ReadQuery
{
    // constructor receives container instance
    public function __construct(protected ORM\EntityManager $entityManager)
    {
    }

    /**
     * GET /api/v1/users/{userId}
     *
     * Summary: Returns a user based on a single userId
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
        if ($args['userId'] <= 0 || $args['userId'] > 2147483647) { // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $user = $this->entityManager->getRepository(User::class)->find($args['userId']);
        if (!$user instanceof User) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Caching with ETag
        $etag = md5(json_encode($user) . $user->getPassword());
        if (in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED); // 304
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson($user);
    }
}
