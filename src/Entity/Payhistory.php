<?php

/*
 * (c) Sven Nolting, 2023
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payhistory.
 *
 * @ORM\Table(name="payhistory", indexes={@ORM\Index(name="paypalme_id", columns={"paypalme_id"})})
 *
 * @ORM\Entity(repositoryClass="App\Repository\PayhistoryRepository")
 */
class Payhistory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created;

    /**
     * @var Paypalmes|null
     *
     * @ORM\ManyToOne(targetEntity="Paypalmes")
     *
     * @ORM\JoinColumns({
     *
     *   @ORM\JoinColumn(name="paypalme_id", referencedColumnName="id")
     * })
     */
    private $paypalme;

    /**
     * @var string|null
     *
     * @ORM\Column(name="clicked_by", type="string", length=255, nullable=true)
     */
    private $clickedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getPaypalme(): ?Paypalmes
    {
        return $this->paypalme;
    }

    public function setPaypalme(?Paypalmes $paypalme): self
    {
        $this->paypalme = $paypalme;

        return $this;
    }

    /**
     * Get the value of clickedBy.
     */
    public function getClickedBy(): ?string
    {
        return $this->clickedBy;
    }

    /**
     * Set the value of clickedBy.
     */
    public function setClickedBy(string $clickedBy): self
    {
        $this->clickedBy = $clickedBy;

        return $this;
    }
}
