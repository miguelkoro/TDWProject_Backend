<?php

/**
 * src/Entity/Association.php
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

#[ORM\Entity, ORM\Table(name: "associations")]
#[ORM\UniqueConstraint(name: "Association_name_uindex", columns: [ "name" ])]
class Association extends Element
{
    /* Set of entities participating in the association */
    /*#[ORM\ManyToMany(targetAssociation: Entity::class, inversedBy: "associations")]
    #[ORM\JoinTable(name: "entity_participates_association")]
    #[ORM\JoinColumn(name: "association_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "entity_id", referencedColumnName: "id")]
    protected Collection $entities;*/
    #[ORM\ManyToMany(targetEntity: Entity::class, mappedBy: "associations")]
    #[ORM\OrderBy([ "id" => "ASC" ])]
    protected Collection $entities;

    /* Collection of products the entity is involved in */
    //#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: "entities")]
    //#[ORM\OrderBy([ "id" => "ASC" ])]
    //protected Collection $products;

    /**
     * Association constructor.
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
        /* Initialize entities collection */
        $this->entities = new ArrayCollection();
        /* Initialize products collection */
        //$this->products = new ArrayCollection();
    }

    // Entities

    /**
     * Gets the entities who are part of the association
     *
     * @return Collection<Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    /**
     * Determines if a entity is part of the association
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
     * Add a entity to the association
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
     * Remove a association from the entity
     *
     * @param Entity $entity
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEntity(Entity $entity): bool
    {
        return $this->entities->removeElement($entity);
    }

    // Products

    /**
     * Obtains the products in which the entity participates
     *
     * @return Collection<Product>
     */
    /*public function getProducts(): Collection
    {
        return $this->products;
    }*/

    /**
     * Determines whether the entity participates in the creation of the product
     *
     * @param Product $product
     * @return bool
     */
    /*public function containsProduct(Product $product): bool
    {
        return $this->products->contains($product);
    }*/

    /**
     * Add a product to this entity
     *
     * @param Product $product
     *
     * @return void
     */
    /*public function addProduct(Product $product): void
    {
        $this->products->add($product);
        $product->addEntity($this);
    }*/

    /**
     * Delete a product from this entity
     *
     * @param Product $product
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    /*public function removeProduct(Product $product): bool
    {
        $result = $this->products->removeElement($product);
        $product->removeEntity($this);
        return $result;
    }*/

    /**
     * @see \Stringable
     */
    public function __toString(): string
    {
        return sprintf(
            //'%s persons=%s, products=%s)]',
            '%s entities=%s)]',
            parent::__toString(),
            $this->getCodesStr($this->getEntities()),
            //$this->getCodesStr($this->getProducts())
        );
    }

    /**
     * @see \JsonSerializable
     */
    #[ArrayShape(['association' => "array|mixed"])]
    public function jsonSerialize(): mixed
    {
        /* Reflection to examine the instance */
        $reflection = new ReflectionObject($this);
        $data = parent::jsonSerialize();
        $numEntities = count($this->getEntities());
        $data['entities'] = $numEntities !== 0 ? $this->getCodes($this->getEntities()) : null;
        //$numPersons = count($this->getPersons());
        //$data['persons'] = $numPersons !== 0 ? $this->getCodes($this->getPersons()) : null;

        return [strtolower($reflection->getShortName()) => $data];
    }
}
