<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Please select a booking date.'),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Please select a start time.'),
                ],
            ])
            ->add('hours', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Please specify number of hours.'),
                    new Positive(message: 'Hours must be at least 1.'),
                ],
            ])
            ->add('tier', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Pricing tier is required.'),
                ],
            ])
            ->add('station', TextType::class, [
                'mapped' => false,
                'required' => false,
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
