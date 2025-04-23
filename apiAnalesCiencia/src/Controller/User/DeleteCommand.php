<?php

/**
 * src/Controller/User/DeleteCommand.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\User;

use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\TraitController;
use TDW\ACiencia\Entity\User;
use TDW\ACiencia\Utility\Error;

class DeleteCommand
{
    use TraitController;

    public function __construct(protected ORM\EntityManager $entityManager)
    {
    }

    /**
     * DELETE /api/v1/users/{userId}
     *
     * Summary: Deletes a user
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'DELETE');
        if (!$this->checkWriterScope($request)) { // 403 => 404 por seguridad
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        if ($args['userId'] <= 0 || $args['userId'] > 2147483647) { // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $this->entityManager->beginTransaction();
        $user = $this->entityManager->getRepository(User::class)->find($args['userId']);

        if (!$user instanceof User) {    // 404
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withStatus(StatusCode::STATUS_NO_CONTENT);  // 204
    }
}
