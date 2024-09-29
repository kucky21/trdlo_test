<?php

namespace App\Service;

use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function calculateTotalAmount(Invoice $invoice): void
    {
        $totalAmount = 0;

        foreach ($invoice->getItems() as $item) {
            $totalAmount += $item->getQuantity() * $item->getUnitPrice();
        }

        $invoice->setTotalAmount($totalAmount);
    }

    public function generateInvoiceNumber(Invoice $invoice): void
    {
        $issueDate = $invoice->getIssueDate();
        if (!$issueDate) {
            throw new \LogicException('Issue date must be set to generate the invoice number.');
        }

        $year = $issueDate->format('Y');

        $lastInvoice = $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->where('i.invoiceNumber LIKE :year')
            ->setParameter('year', $year . '%')
            ->orderBy('i.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastInvoice && preg_match('/^' . $year . '(\d{4})$/', $lastInvoice->getInvoiceNumber(), $matches)) {
            $lastSequentialNumber = (int) $matches[1];
            $nextSequentialNumber = $lastSequentialNumber + 1;
        } else {
            $nextSequentialNumber = 1;
        }

        $newInvoiceNumber = $year . str_pad($nextSequentialNumber, 4, '0', STR_PAD_LEFT);
        $invoice->setInvoiceNumber($newInvoiceNumber);
    }
}
