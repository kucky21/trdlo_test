<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Item;
use App\Form\InvoiceType;
use App\Form\ItemType;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\InvoiceCreatedEvent;
use Knp\Component\Pager\PaginatorInterface;

class InvoiceController extends AbstractController
{
    private $entityManager;
    private $invoiceService;
    private $logger;
    private $cache;
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        InvoiceService $invoiceService,
        LoggerInterface $logger,
        CacheInterface $cache,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('/', name: 'invoice_list')]
    public function list(InvoiceRepository $invoiceRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $this->logger->info('Fetching invoices from cache or database');
        $pagination = $this->cache->get('invoice_list', function () use ($invoiceRepository, $paginator, $request) {
            $queryBuilder = $invoiceRepository->createQueryBuilder('i');
            return $paginator->paginate(
                $queryBuilder,
                $request->query->getInt('page', 1),
                10
            );
        });

        return $this->render('invoice/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/invoice/{id}/edit', name: 'invoice_edit', requirements: ['id' => '\d+'])]
    public function editInvoice(Request $request, Invoice $invoice): Response
    {
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            return $this->redirectToRoute('invoice_list');
        }

        return $this->render('invoice/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => true,
        ]);
    }

    #[Route('/invoice/new', name: 'invoice_new')]
    public function newInvoice(Request $request): Response
    {
        $invoice = new Invoice();

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->invoiceService->calculateTotalAmount($invoice);

            if (!$invoice->getInvoiceNumber()) {
                $this->invoiceService->generateInvoiceNumber($invoice);
            }

            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new InvoiceCreatedEvent($invoice), InvoiceCreatedEvent::NAME);

            return $this->redirectToRoute('invoice_list');
        }

        return $this->render('invoice/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => false,
        ]);
    }

    #[Route('/invoice/{id}/delete', name: 'invoice_delete', methods: ['POST'])]
    public function deleteInvoice(Request $request, Invoice $invoice): Response
    {
        if ($this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($invoice);
            $this->entityManager->flush();

            $this->addFlash('success', 'Invoice deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('invoice_list');
    }

    #[Route('/invoice/{id}/add-item', name: 'invoice_add_item')]
    public function addItem(Request $request, Invoice $invoice): Response
    {
        $item = new Item();
        $item->setInvoice($invoice);

        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            $this->invoiceService->calculateTotalAmount($invoice);
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            return $this->redirectToRoute('invoice_list');
        }

        return $this->render('invoice/add_item.html.twig', [
            'invoice' => $invoice,
            'form' => $form->createView(),
        ]);
    }
}
