<?php

namespace App\EventListener;

use App\Event\InvoiceCreatedEvent;
use Psr\Log\LoggerInterface;

class InvoiceListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onInvoiceCreated(InvoiceCreatedEvent $event)
    {
        $invoice = $event->getInvoice();
        $this->logger->info('Invoice created: ' . $invoice->getId());
    }
}
