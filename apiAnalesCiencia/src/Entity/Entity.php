<?php

/**
 * src/Entity/Entity.php
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

#[ORM\Entity, ORM\Table(name: "entities")]
#[ORM\UniqueConstraint(name: "Entity_name_uindex", columns: [ "name" ])]
class Entity extends Element
{
    /* Set of people participating in the entity */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: "entities")]
    #[ORM\JoinTable(name: "person_participates_entity")]
    #[ORM\JoinColumn(name: "entity_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "person_id", referencedColumnName: "id")]
    protected Collection $persons;

    /* Collection of products the entity is involved in */
    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: "entities")]
    #[ORM\OrderBy([ "id" => "ASC" ])]
    protected Collection $products;

    /**
     * Entity constructor.
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
        /* Initialize persons collection */
        $this->persons = new ArrayCollection();
        /* Initialize products collection */
        $this->products = new ArrayCollection();
    }

    // Persons

    /**
     * Gets the persons who are part of the entity
     *
     * @return Collection<Person>
     */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    /**
     * Determines if a person is part of the entity
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
     * Add a person to the entity
     *
     * @param Person $person
     *
     * @return void
     */
    public function addPerson(Person $person): void
    {
        if ($this->containsPerson($person)) {
            return;
        }

        $this->persons->add($person);
    }

    /**
     * Remove a person from the entity
     *
     * @param Person $person
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePerson(Person $person): bool
    {
        return $this->persons->removeElement($person);
    }

    // Products

    /**
     * Obtains the products in which the entity participates
     *
     * @return Collection<Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * Determines whether the entity participates in the creation of the product
     *
     * @param Product $product
     * @return bool
     */
    public function containsProduct(Product $product): bool
    {
        return $this->products->contains($product);
    }

    /**
     * Add a product to this entity
     *
     * @param Product $product
     *
     * @return void
     */
    public function addProduct(Product $product): void
    {
        $this->products->add($product);
        $product->addEntity($this);
    }

    /**
     * Delete a product from this entity
     *
     * @param Product $product
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProduct(Product $product): bool
    {
        $result = $this->products->removeElement($product);
        $product->removeEntity($this);
        return $result;
    }

    /**
     * @see \Stringable
     */
    public function __toString(): string
    {
        return sprintf(
            '%s persons=%s, products=%s)]',
            parent::__toString(),
            $this->getCodesStr($this->getPersons()),
            $this->getCodesStr($this->getProducts())
        );
    }

    /**
     * @see \JsonSerializable
     */
    #[ArrayShape(['entity' => "array|mixed"])]
    public function jsonSerialize(): mixed
    {
        /* Reflection to examine the instance */
        $reflection = new ReflectionObject($this);
        $data = parent::jsonSerialize();
        $numProducts = count($this->getProducts());
        $data['products'] = $numProducts !== 0 ? $this->getCodes($this->getProducts()) : null;
        $numPersons = count($this->getPersons());
        $data['persons'] = $numPersons !== 0 ? $this->getCodes($this->getPersons()) : null;

        return [strtolower($reflection->getShortName()) => $data];
    }
}
