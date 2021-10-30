<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Orders
 *
 * @ORM\Table(name="orders", indexes={@ORM\Index(name="FK_orders_hirsch", columns={"name"})})
 * @ORM\Entity(repositoryClass="App\Repository\OrdersRepository")
 */
class Orders
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="string", length=1000, nullable=false, options={"default"="''"})
     */
    private $note = '\'\'';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="for", type="date", nullable=false)
     */
    private $for;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    private $created = 'current_timestamp()';

    /**
     * @var string
     *
     * @ORM\Column(name="orderedby", type="string", length=255, nullable=false)
     */
    private $orderedby;

    /**
     * @var \Hirsch
     *
     * @ORM\ManyToOne(targetEntity="Hirsch")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="name", referencedColumnName="slug")
     * })
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getFor(): ?\DateTimeInterface
    {
        return $this->for;
    }

    public function setFor(\DateTimeInterface $for): self
    {
        $this->for = $for;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getOrderedby(): ?string
    {
        return $this->orderedby;
    }

    public function setOrderedby(string $orderedby): self
    {
        $this->orderedby = $orderedby;

        return $this;
    }

    public function getName(): ?Hirsch
    {
        return $this->name;
    }

    public function setName(?Hirsch $name): self
    {
        $this->name = $name;

        return $this;
    }


}
