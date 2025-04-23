<?php

/**
 * src/Entity/Product.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

use DateTime;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionObject;

#[ORM\Entity, ORM\Table(name: "products")]
#[ORM\UniqueConstraint(name: "Product_name_uindex", columns: [ "name" ])]
class Product extends Element
{
    /* Collection of people involved in the product */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: "products")]
    #[ORM\JoinTable(name: "person_contributes_product")]
    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "person_id", referencedColumnName: "id")]
    protected Collection $persons;

    /* Collection of entities involved in the product */
    #[ORM\ManyToMany(targetEntity: Entity::class, inversedBy: "products")]
    #[ORM\JoinTable(name: "entity_contributes_product")]
    #[ORM\JoinColumn(name: "product_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "entity_id", referencedColumnName: "id")]
    protected Collection $entities;

    /**
     * Product constructor.
     *
     * @param non-empty-string $name
     * @param DateTime|null $birthDate
     * @param DateTime|null $deathDate
     * @param string|null $imageUrl
     * @param string|null $wikiUrl
     */
    public function __construct(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ) {
        parent::__construct($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
        /* Initialize collections */
        $this->persons = new ArrayCollection();
        $this->entities = new ArrayCollection();
    }

    // Entities

    /**
     * Gets the entities that participate in the product
     *
     * @return Collection<Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    /**
     * Indicates whether an entity participates in this product
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function containsEntity(Entity $entity): bool
    {
        return $this->entities->contains($entity);
    }

    /**
     * Add an entity to this product
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function addEntity(Entity $entity): void
    {
        if (!$this->containsEntity($entity)) {
            $this->entities->add($entity);
        }
    }

    /**
     * Removes an entity from this product
     *
     * @param Entity $entity
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEntity(Entity $entity): bool
    {
        return $this->entities->removeElement($entity);
    }

    // Persons

    /**
     * Gets people to collaborate on this product
     *
     * @return Collection<Person>
     */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    /**
     * Determine if a person collaborates on this product
     *
     * @param Person $person
     *
     * @return bool
     */
    public function containsPerson(Person $person): bool
    {
        return $this->persons->contains($person);
    }

    /**
     * Add a person to this product
     *
     * @param Person $person
     *
     * @return void
     */
    public function addPerson(Person $person): void
    {
        if (!$this->containsPerson($person)) {
            $this->persons->add($person);
        }
    }

    /**
     * Removes a person from this product
     *
     * @param Person $person
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePerson(Person $person): bool
    {
        return $this->persons->removeElement($person);
    }

    /**
     * @see \Stringable
     */
    public function __toString(): string
    {
        return sprintf(
            '%s persons=%s, entities=%s)]',
            parent::__toString(),
            $this->getCodesStr($this->getPersons()),
            $this->getCodesStr($this->getEntities())
        );
    }

    /**
     * @see \JsonSerializable
     */
    #[ArrayShape(['product' => "array|mixed"])]
    public function jsonSerialize(): mixed
    {
        /* Reflection to examine the instance */
        $reflection = new ReflectionObject($this);
        $data = parent::jsonSerialize();
        $numPersons = count($this->getPersons());
        $data['persons'] = $numPersons !== 0 ? $this->getCodes($this->getPersons()) : null;
        $numEntities = count($this->getEntities());
        $data['entities'] = $numEntities !== 0 ? $this->getCodes($this->getEntities()) : null;

        return [strtolower($reflection->getShortName()) => $data];
    }
}
