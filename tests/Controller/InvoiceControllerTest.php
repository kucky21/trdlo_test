<?php

namespace App\Tests\Controller;

use App\Controller\InvoiceController;
use App\Entity\Invoice;
use App\Entity\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

class InvoiceControllerTest extends TestCase
{
    public function testAddItemAndRecalculateTotal()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createMock(FormInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices');

        $request = new Request();

        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $formFactory->method('create')->willReturn($form);

        $invoice = $this->createPartialMock(Invoice::class, ['getItems', 'setTotalAmount']);

        $item1 = $this->createMock(Item::class);
        $item1->method('getQuantity')->willReturn(2);
        $item1->method('getUnitPrice')->willReturn(50.0); 

        $item2 = $this->createMock(Item::class);
        $item2->method('getQuantity')->willReturn(1);
        $item2->method('getUnitPrice')->willReturn(100.0); 

        $items = new ArrayCollection([$item1, $item2]);

        $invoice->method('getItems')->willReturn($items);

        $invoice->expects($this->once())->method('setTotalAmount')->with(200.0);

        $entityManager->expects($this->exactly(2))->method('persist');
        $entityManager->expects($this->exactly(2))->method('flush');

        $controller = new InvoiceController();
        $controller->setContainer($this->createMockContainer($formFactory, $router));

        $response = $controller->addItem($request, $invoice, $entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/invoices', $response->headers->get('Location'));
    }

    private function createMockContainer($formFactory, $router)
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            ['form.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $formFactory],
            ['router', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $router],
        ]);

        return $container;
    }
}
