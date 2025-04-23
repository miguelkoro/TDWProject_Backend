<?php

/**
 * src/Entity/ElementInterface.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

use DateTime;
use JsonSerializable;
use Stringable;

interface ElementInterface extends JsonSerializable, Stringable
{
    /**
     * Gets the Element's Id
     *
     * @return non-negative-int Element id
     */
    public function getId(): int;

    /**
     * Gets the Element's name
     *
     * @return non-empty-string Element name
     */
    public function getName(): string;

    /**
     * Sets the Element's name
     *
     * @param non-empty-string $name new Element name
     * @return void
     */
    public function setName(string $name): void;

    /**
     * Gets the Element's BirthDate
     *
     * @return DateTime|null Element birthdate
     */
    public function getBirthDate(): ?DateTime;

    /**
     * Sets the Element's BirthDate
     *
     * @param DateTime|null $birthDate Element birthdate
     * @return void
     */
    public function setBirthDate(?DateTime $birthDate): void;

    /**
     * Gets the Element's DeathDate
     *
     * @return DateTime|null Element deathdate
     */
    public function getDeathDate(): ?DateTime;

    /**
     * Sets the Element's DeathDate
     *
     * @param DateTime|null $deathDate Element deathdate
     * @return void
     */
    public function setDeathDate(?DateTime $deathDate): void;

    /**
     * Gets the Element's ImageUrl
     *
     * @return string|null Element Image Url
     */
    public function getImageUrl(): ?string;

    /**
     * Sets the Element's ImageUrl
     *
     * @param string|null $imageUrl Element Image Url
     * @return void
     */
    public function setImageUrl(?string $imageUrl): void;

    /**
     * Gets the Element's WikiUrl
     *
     * @return string|null Element Wiki Url
     */
    public function getWikiUrl(): ?string;

    /**
     * Sets the Element's WikiUrl
     *
     * @param string|null $wikiUrl Element Wiki Url
     * @return void
     */
    public function setWikiUrl(?string $wikiUrl): void;

    /**
     * @see Stringable
     */
    public function __toString(): string;

    /**
     * @see JsonSerializable
     */
    public function jsonSerialize(): mixed;
}
