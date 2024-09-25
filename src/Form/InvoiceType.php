<?php

namespace App\Form;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', TextType::class, [
                'label' => 'Customer',
                'required' => true,
            ])
            ->add('supplier', TextType::class, [
                'label' => 'Supplier',
                'required' => true,
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'Issue Date',
                'widget' => 'single_text',
                'html5' => true,      
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Due Date',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('paymentDate', DateType::class, [
                'label' => 'Payment Date',
                'widget' => 'single_text',
                'required' => false,
                'html5' => true,
            ])
            ->add('paymentMethod', TextType::class, [
                'label' => 'Payment Method',
                'required' => true,
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => ItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
