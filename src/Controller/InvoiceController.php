<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Item;
use App\Form\InvoiceType;
use App\Form\ItemType;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class InvoiceController extends AbstractController
{
    #[Route('/', name: 'invoice_list')]
    public function list(InvoiceRepository $invoiceRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $invoiceRepository->createQueryBuilder('i');
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('invoice/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/invoice/{id}/edit', name: 'invoice_edit', requirements: ['id' => '\d+'])]
    public function editInvoice(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($invoice);
            $em->flush();

            return $this->redirectToRoute('invoice_list');
        }

        return $this->render('invoice/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => true,
        ]);
    }

    #[Route('/invoice/new', name: 'invoice_new')]
    public function newInvoice(EntityManagerInterface $em, Request $request): Response
    {
        $invoice = new Invoice();
    
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->calculateTotalAmount($invoice);
    
            if (!$invoice->getInvoiceNumber()) {
                $this->generateInvoiceNumber($invoice, $em);
            }
    
            $em->persist($invoice);
            $em->flush();
    
            return $this->redirectToRoute('invoice_list');
        }
    
        return $this->render('invoice/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => false,
        ]);
    }

    #[Route('/invoice/{id}/delete', name: 'invoice_delete', methods: ['POST'])]
    public function deleteInvoice(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$invoice->getId(), $request->request->get('_token'))) {
            $em->remove($invoice);
            $em->flush();

            $this->addFlash('success', 'Invoice deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('invoice_list');
    }

    #[Route('/invoice/{id}/add-item', name: 'invoice_add_item')]
    public function addItem(Request $request, Invoice $invoice, EntityManagerInterface $em): Response
    {
        $item = new Item();
        $item->setInvoice($invoice);
    
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($item);
            $em->flush();
    
            $this->calculateTotalAmount($invoice);

            $em->persist($invoice);
            $em->flush();
    
            return $this->redirectToRoute('invoice_list');
        }
    
        return $this->render('invoice/add_item.html.twig', [
            'invoice' => $invoice,
            'form' => $form->createView(),
        ]);
    }
    
    
private function calculateTotalAmount(Invoice $invoice): void
{
    $totalAmount = 0;

    foreach ($invoice->getItems() as $item) {
        $totalAmount += $item->getQuantity() * $item->getUnitPrice();
    }

    $invoice->setTotalAmount($totalAmount);
}

    
    private function generateInvoiceNumber(Invoice $invoice, EntityManagerInterface $em): void
{
    $issueDate = $invoice->getIssueDate();
    if (!$issueDate) {
        throw new \LogicException('Issue date must be set to generate the invoice number.');
    }

    $year = $issueDate->format('Y');

    $lastInvoice = $em->getRepository(Invoice::class)
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
