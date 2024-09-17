<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Customer name should not be blank.')]
    #[Assert\Length(
        min: 4,
        max: 64,
        minMessage: 'Customer name must be at least {{ limit }} characters long.',
        maxMessage: 'Customer name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $customer = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Supplier name should not be blank.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Supplier name must be at least {{ limit }} characters long.',
        maxMessage: 'Supplier name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $supplier = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Issue date should not be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'The value {{ value }} is not a valid date.')]
    private ?\DateTimeInterface $issueDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Due date should not be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'The value {{ value }} is not a valid date.')]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Payment date should not be blank.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'The value {{ value }} is not a valid date.')]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Payment method should not be blank.')]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'string', length: 8, unique: true)]
    #[Assert\NotBlank(message: 'Invoice number should not be blank.')]
    #[Assert\Length(
        min: 8,
        max: 8,
        exactMessage: 'Invoice number must be exactly {{ limit }} characters long.'
    )]
    private ?string $invoiceNumber = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Total amount should not be blank.')]
    #[Assert\PositiveOrZero(message: 'Total amount must be zero or positive.')]
    private ?float $totalAmount = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Item::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeInterface $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->removeElement($item)) {
            // Set the owning side to null (unless already changed)
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }
        }

        return $this;
    }
}
