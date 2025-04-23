<?php

declare(strict_types=1);

/**
 * src/Entity/Element.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Element
 */
class Element implements ElementInterface
{
    #[ORM\Column(
        name: "id",
        type: "integer",
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    #[ORM\Column(
        name: "name",
        type: "string",
        length: 80,
        unique: true,
        nullable: false
    )]
    /** @phpstan-type non-empty-string */
    protected string $name;

    #[ORM\Column(
        name: "birth_date",
        type: "datetime",
        nullable: true
    )]
    protected DateTime | null $birthDate = null;

    #[ORM\Column(
        name: "death_date",
        type: "datetime",
        nullable: true
    )]
    protected DateTime | null $deathDate = null;

    #[ORM\Column(
        name: "image_url",
        type: "string",
        length: 2047,
        nullable: true
    )]
    protected string | null $imageUrl = null;

    #[ORM\Column(
        name: "wiki_url",
        type: "string",
        length: 2047,
        nullable: true
    )]
    protected string | null $wikiUrl = null;

    /**
     * Element's Constructor
     *
     * @param non-empty-string $name
     * @param DateTime|null $birthDate
     * @param DateTime|null $deathDate
     * @param string|null $imageUrl
     * @param string|null $wikiUrl
     */
    protected function __construct(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ) {
        assert($name !== '');
        $this->id = 0;
        $this->name = $name;
        $this->birthDate = $birthDate;
        $this->deathDate = $deathDate;
        $this->imageUrl = $imageUrl;
        $this->wikiUrl = $wikiUrl;
    }

    /**
     * Gets the element's ID
     *
     * @return int<0, max>
     */
    final public function getId(): int
    {
        assert($this->id >= 0);
        return $this->id;
    }

    /**
     * Gets the element's name
     *
     * @return non-empty-string
     */
    final public function getName(): string
    {
        assert($this->name !== '');
        return $this->name;
    }

    /**
     * Sets the element's name
     *
     * @param non-empty-string $name
     */
    final public function setName(string $name): void
    {
        assert($name !== '');
        $this->name = $name;
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

    /**
     * Gets the element's deathdate
     *
     * @return ?DateTime
     */
    final public function getDeathDate(): ?DateTime
    {
        return $this->deathDate;
    }

    /**
     * Sets the element's deathdate
     *
     * @param DateTime|null $deathDate
     * @return void
     */
    final public function setDeathDate(?DateTime $deathDate): void
    {
        $this->deathDate = $deathDate;
    }

    /**
     * Gets the element's ImageUrl
     *
     * @return ?string
     */
    final public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Sets the element's ImageUrl
     *
     * @param string|null $imageUrl
     */
    final public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * Gets the element's WikiUrl
     *
     * @return ?string
     */
    final public function getWikiUrl(): ?string
    {
        return $this->wikiUrl;
    }

    /**
     * Sets the element's WikiUrl
     *
     * @param string|null $wikiUrl
     */
    final public function setWikiUrl(?string $wikiUrl): void
    {
        $this->wikiUrl = $wikiUrl;
    }

    /**
     * Obtains an sorted array with element Ids
     *
     * @param Collection<ElementInterface> $collection
     *
     * @return int[] sorted Ids in collection
     */
    #[ArrayShape([ "int" => "int" ])]
    final protected function getCodes(Collection $collection): array
    {
        $arrayIds = array_map(
            fn(Element $element) => $element->getId(),
            $collection->getValues()
        );
        sort($arrayIds);
        return $arrayIds;
    }

    /**
     * Gets the string representation of the code array
     *
     * @param Collection<ElementInterface> $collection
     *
     * @return string String representation of Collection Ids
     */
    final protected function getCodesStr(Collection $collection): string
    {
        $codes = $this->getCodes($collection);
        return sprintf('[%s]', implode(', ', $codes));
    }

    /** @see \Stringable */
    public function __toString(): string
    {
        $reflection = new \ReflectionObject($this);
        return sprintf(
            '[%s: (id=%04d, name="%s", birthDate="%s", deathDate="%s", imageUrl="%s", wikiUrl="%s"',
            $reflection->getShortName(),
            $this->getId(),
            $this->getName(),
            $this->getBirthDate()?->format('Y-m-d'),
            $this->getDeathDate()?->format('Y-m-d'),
            $this->getImageUrl(),
            $this->getWikiUrl(),
        );
    }

    /** @see \JsonSerializable */
    #[ArrayShape([
        'id' => "int",
        'name' => "string",
        'birthDate' => "null|string",
        'deathDate' => "null|string",
        'imageUrl' => "null|string",
        'wikiUrl' => "null|string"
    ])]
    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'birthDate' => $this->getBirthDate()?->format('Y-m-d'),
            'deathDate' => $this->getDeathDate()?->format('Y-m-d'),
            'imageUrl'  => $this->getImageUrl(),
            'wikiUrl'  => $this->getWikiUrl(),
        ];
    }
}
