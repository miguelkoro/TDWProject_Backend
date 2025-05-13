<?php

/**
 * src/Controller/User/UpdateCommand.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Controller\User;
use DateTime;
use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\ACiencia\Controller\TraitController;
use TDW\ACiencia\Entity\User;
use TDW\ACiencia\Utility\Error;
use Throwable;

/**
 * Class UpdateCommand
 */
class UpdateCommand
{
    use TraitController;

    /** @var string ruta api gestión usuarios  */
    public const PATH_USERS = '/users';

    // constructor receives container instance
    public function __construct(protected ORM\EntityManager $entityManager)
    {
    }

    /**
     * PUT /api/v1/users/{userId}
     *
     * Summary: Updates a user
     * - A READER user can only modify their own properties
     * - A READER user cannot modify his ROLE
     *
     * @param Request $request
     * @param Response $response
     * @param array<non-empty-string,non-empty-string> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'PUT');
        assert(intval($args['userId']) !== 0);
        $isWriter = $this->checkWriterScope($request);
        $userRequestId = $this->getUserId($request);
        if (!$isWriter && intval($args['userId']) !== $userRequestId) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND); // 403 => 404 por seguridad
        }

        // Check the userId range: 2147483647 > userId > 0
        if ($args['userId'] <= 0 || $args['userId'] > 2147483647) { // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        /** @var array<string,string> $req_data */
        $req_data = $request->getParsedBody() ?? [];
        $this->entityManager->beginTransaction();
        /** @var User|null $userToModify */
        $userToModify = $this->entityManager->getRepository(User::class)->find($args['userId']);

        // Check whether the user exists
        if (!$userToModify instanceof User) {    // 404
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Optimistic Locking (strong validation) - https://httpwg.org/specs/rfc6585.html#status-428
        $etag = md5(json_encode($userToModify) . $userToModify->getPassword());
        if (!in_array($etag, $request->getHeader('If-Match'), true)) {
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);   // 428
        }

        // Checks whether the user with _username_ name already exists
        if (isset($req_data['username'])) { // Update username
            assert($req_data['username'] !== '');
            $usuarioId = $this->findIdBy('username', $req_data['username']);
            if (($usuarioId !== 0) && intval($args['userId']) !== $usuarioId) { // 400
                $this->entityManager->rollback();
                // 400 BAD_REQUEST: username already exists
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $userToModify->setUsername($req_data['username']);
        }

        // Update e-mail
        if (isset($req_data['email'])) {
            $usuarioId = $this->findIdBy('email', $req_data['email']);
            if (($usuarioId !== 0) && intval($args['userId']) !== $usuarioId) {
                $this->entityManager->rollback();
                // 400 BAD_REQUEST: e-mail already exists
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $userToModify->setEmail($req_data['email']);
        }

        // Check BirthDate
        if (isset($req_data['birthDate'])) {
            try{
                $birthDate = new DateTime($req_data['birthDate']);
                $userToModify->setBirthDate($birthDate);
            } catch (Throwable) {    // 400 BAD_REQUEST: unexpected date format
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
        }

        //Check name
        if (isset($req_data['name'])) {
            try{
                $userToModify->setName($req_data['name']);
            }catch (Throwable) {    // 400 BAD_REQUEST: unexpected date format
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
        }

        $this->updatePassword($req_data, $userToModify);

        // Update role
        if ($isWriter && isset($req_data['role'])) {
            try {
                $userToModify->setRole($req_data['role']);
            } catch (Throwable) {    // 400 BAD_REQUEST: unexpected role
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withJson($userToModify, 209);
    }

    /**
     * Determines if a user exists with a certain value for an attribute
     *
     * @return int User id (0 if does not exist)
     */
    private function findIdBy(string $attr, string $value): int
    {
        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy([ $attr => $value ]);
        return $user?->getId() ?? 0;
    }

    /**
     * Update the user's password
     *
     * @param array<string,string> $req_data
     * @param User $userToModify
     * @return void
     */
    private function updatePassword(array $req_data, User $userToModify): void
    {
        // Update password
        if (isset($req_data['password'])) {
            $userToModify->setPassword($req_data['password']);
        }
    }
}
