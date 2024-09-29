<?php

namespace App\Event;

use App\Entity\Invoice;
use Symfony\Contracts\EventDispatcher\Event;

class InvoiceCreatedEvent extends Event
{
    public const NAME = 'invoice.created';

    private $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
