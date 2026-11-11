<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Station;
use App\Repository\StationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

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
                    new Range(min: 1, max: 12, notInRangeMessage: 'Choose between {{ min }} and {{ max }} hours.'),
                ],
                'attr' => ['min' => 1, 'max' => 12, 'step' => 1],
            ])
            ->add('station', EntityType::class, [
                'class' => Station::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a station',
                'query_builder' => static fn (StationRepository $repository) => $repository
                    ->createQueryBuilder('station')
                    ->orderBy('station.type', 'ASC')
                    ->addOrderBy('station.name', 'ASC'),
                'required' => true,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Cash at counter — pay in person' => 'Cash at Counter',
                    'GCash (demo only)' => 'GCash',
                    'Maya (demo only)' => 'Maya',
                ],
                'required' => true,
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
