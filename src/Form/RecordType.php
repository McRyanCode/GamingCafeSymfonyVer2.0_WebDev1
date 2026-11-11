<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Station;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class RecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tier', ChoiceType::class, [
                'label' => 'Gaming Tier / Package',
                'choices' => [
                    'Basic Tier (₱40/hr)' => 'BASIC',
                    'Standard Tier (3 Hrs - ₱100)' => 'STANDARD',
                    'Premium Tier (5 Hrs - ₱150)' => 'PREMIUM',
                    'Pro Gaming Rig (₱80/hr)' => 'Pro Gaming Rig',
                    'VIP Ultra Rig (₱100/hr)' => 'VIP Ultra Rig',
                    'Standard Rig (₱60/hr)' => 'Standard Rig',
                ],
                'attr' => ['class' => 'admin-select'],
                'constraints' => [
                    new NotBlank(message: 'Please select a tier.'),
                ],
            ])
            ->add('station', EntityType::class, [
                'class' => Station::class,
                'choice_label' => function (Station $station) {
                    return sprintf('%s (%s)', $station->getName(), $station->getType());
                },
                'placeholder' => '-- Select Station (Optional) --',
                'required' => false,
                'attr' => ['class' => 'admin-select'],
            ])
            ->add('hours', IntegerType::class, [
                'label' => 'Session Hours',
                'attr' => [
                    'class' => 'admin-input',
                    'min' => 1,
                    'max' => 24,
                ],
                'constraints' => [
                    new NotBlank(message: 'Please specify hours.'),
                    new Positive(message: 'Hours must be at least 1.'),
                ],
            ])
            ->add('bookingDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'admin-input'],
                'constraints' => [
                    new NotBlank(message: 'Please choose a date.'),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Start Time',
                'widget' => 'single_text',
                'attr' => ['class' => 'admin-input'],
                'constraints' => [
                    new NotBlank(message: 'Please specify start time.'),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Record Status',
                'choices' => [
                    'Confirmed / Active' => 'CONFIRMED',
                    'Completed Session' => 'COMPLETED',
                    'Cancelled' => 'CANCELLED',
                ],
                'attr' => ['class' => 'admin-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
