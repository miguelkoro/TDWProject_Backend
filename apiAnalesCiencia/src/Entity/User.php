<?php

declare(strict_types=1);

/**
 * src/Entity/User.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use ReflectionObject;
use Stringable;
use ValueError;

#[ORM\Entity, ORM\Table(name: "users")]
class User implements JsonSerializable, Stringable
{
    #[ORM\Column(
        name: "id",
        type: "integer",
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    #[ORM\Column(
        name: "username",
        type: "string",
        length: 32,
        unique: true,
        nullable: false
    )]
    /** @phpstan-type non-empty-string */
    protected string $username;

    #[ORM\Column(
        name: "email",
        type: "string",
        length: 60,
        unique: true,
        nullable: false
    )]
    protected string $email;

    #[ORM\Column(
        name: "password",
        type: "string",
        length: 255,
        nullable: false
    )]
    protected string $password_hash;

    #[ORM\Column(
        name: "role",
        type: "string",
        length: 10,
        nullable: false,
        enumType: Role::class,
        options: [ 'default' => Role::INACTIVE ]
    )]
    protected Role $role;

    #[ORM\Column(
        name: "birth_date",
        type: "datetime",
        nullable: true
    )]
    protected DateTime | null $birthDate = null;

    /**
     * User constructor.
     *
     * @param non-empty-string $username username
     * @param string $email user email
     * @param string $password user password
     * @param Role|string $role Role::*
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $username = '<empty>',
        string $email = '',
        string $password = '',
        Role|string $role = Role::READER,
        DateTime $birthDate = null
    ) {
        $this->id       = 0;
        $this->username = $username;
        $this->email    = $email;
        $this->setPassword($password);
        $this->setRole($role);        
        $this->birthDate = $birthDate;
    }

    /**
     * Gets the user ID
     *
     * @return int User id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get username
     *
     * @phpstan-return non-empty-string
     * @return non-empty-string
     */
    public function getUsername(): string
    {
        assert($this->username !== '');
        return $this->username;
    }

    /**
     * Set username
     *
     * @param non-empty-string $username username
     * @return void
     */
    public function setUsername(string $username): void
    {
        assert($username !== '');
        $this->username = $username;
    }

    /**
     * Get user e-mail
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set user e-mail
     *
     * @param string $email email
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the hashed password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Set the user's password
     *
     * @param string $password password
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies that the given hash matches the user password.
     *
     * @param string $password user password
     * @return boolean Returns TRUE if the password and hash match, or FALSE otherwise.
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->getPassword());
    }

    /**
     * Determines whether the user has a certain role
     *
     * @param Role|string $role
     * @return bool
     */
    public function hasRole(Role|string $role): bool
    {
        if (!$role instanceof Role) {
            $role = Role::tryFrom($role);
        }
        return match ($role) {
            Role::INACTIVE => $this->role->is(Role::INACTIVE),
            Role::READER => !$this->role->is(Role::INACTIVE),
            Role::WRITER => $this->role->is(Role::WRITER),
            default => false
        };
    }

    /**
     * Assign the role to the user
     *
     * @param Role|string $newRole [ Role::READER | Role::WRITER | Role::INACTIVE | 'reader' | 'writer' | 'inactive' ]
     * @return void
     * @throws InvalidArgumentException
     */
    public function setRole(Role|string $newRole): void
    {
        try {
            $this->role = ($newRole instanceof Role)
                ? $newRole
                : Role::from(strtolower($newRole));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid Role');
        }
    }

    /**
     * Returns an array with the user's roles
     *
     * @return Role[] [ INACTIVE] | [ READER ] | [ READER , WRITER ]
     */
    public function getRoles(): array
    {
        $roles = array_filter(
            Role::cases(),
            fn($myRole) => $this->hasRole($myRole)
        );
        return $roles;
    }

    /**
     * Gets the element's bithday
     *
     * @return ?DateTime
     */
    final public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    /**
     * Sets the element's bithday
     *
     * @param DateTime|null $birthDate
     * @return void
     */
    final public function setBirthDate(?DateTime $birthDate): void
    {
        $this->birthDate = $birthDate;
    }


    public function __toString(): string
    {
        $reflection = new ReflectionObject($this);
        return
            sprintf(
                '[%s: (id=%04d, username="%s", email="%s", role="%s")]',
                $reflection->getShortName(),
                $this->getId(),
                $this->getUsername(),
                $this->getEmail(),
                $this->role->name,
                $this->getBirthDate()?->format('Y-m-d')
            );
    }

    /**
     * @see JsonSerializable
     */
    #[ArrayShape(['user' => "array"])]
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        return [
            strtolower($reflection->getShortName()) => [
                'id' => $this->getId(),
                'username' => $this->getUsername(),
                'email' => $this->getEmail(),
                'role' => $this->role->name,
                'birthDate' => $this->getBirthDate()?->format('Y-m-d'),
            ]
        ];
    }
}
