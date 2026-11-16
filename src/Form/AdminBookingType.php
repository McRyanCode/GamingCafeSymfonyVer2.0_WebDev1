<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Station;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class AdminBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $booking = $options['data'];
        $statusChoices = [
            'Confirmed' => 'CONFIRMED',
            'Completed' => 'COMPLETED',
            'Cancelled' => 'CANCELLED',
        ];
        $paymentMethodChoices = [
            'Cash at Counter' => 'Cash at Counter',
            'GCash' => 'GCash',
            'Maya' => 'Maya',
        ];
        $paymentStatusChoices = [
            'Pending' => 'PENDING',
            'Demo Paid' => 'DEMO_PAID',
        ];

        $this->includeCurrentChoice($statusChoices, $booking->getStatus());
        $this->includeCurrentChoice($paymentMethodChoices, $booking->getPaymentMethod());
        $this->includeCurrentChoice($paymentStatusChoices, $booking->getPaymentStatus());

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Customer account',
                'attr' => ['class' => 'admin-input'],
            ])
            ->add('station', EntityType::class, [
                'class' => Station::class,
                'choice_label' => static fn (Station $station): string => sprintf('%s (%s)', $station->getName(), $station->getType()),
                'placeholder' => 'No station assigned',
                'required' => false,
                'attr' => ['class' => 'admin-input'],
            ])
            ->add('tier', TextType::class, [
                'label' => 'Gaming tier / package',
                'attr' => ['class' => 'admin-input'],
                'constraints' => [
                    new NotBlank(message: 'Enter a gaming tier.'),
                    new Length(max: 50),
                ],
            ])
            ->add('bookingDate', DateType::class, [
                'label' => 'Booking date',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: 'Choose a booking date.'),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Start time',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: 'Choose a start time.'),
                ],
            ])
            ->add('hours', IntegerType::class, [
                'label' => 'Duration (hours)',
                'attr' => ['min' => 1, 'max' => 24],
                'constraints' => [
                    new NotBlank(message: 'Enter the session duration.'),
                    new Range(min: 1, max: 24, notInRangeMessage: 'Choose between {{ min }} and {{ max }} hours.'),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => $statusChoices,
                'attr' => ['class' => 'admin-input'],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment method',
                'choices' => $paymentMethodChoices,
                'attr' => ['class' => 'admin-input'],
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'label' => 'Payment status',
                'choices' => $paymentStatusChoices,
                'attr' => ['class' => 'admin-input'],
            ])
            ->add('totalPrice', IntegerType::class, [
                'label' => 'Total price (₱)',
                'attr' => ['min' => 0],
                'constraints' => [
                    new Range(min: 0, notInRangeMessage: 'Total price cannot be negative.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }

    /**
     * @param array<string, string> $choices
     */
    private function includeCurrentChoice(array &$choices, ?string $currentValue): void
    {
        if ($currentValue !== null && !in_array($currentValue, $choices, true)) {
            $choices['Current: '.$currentValue] = $currentValue;
        }
    }
}
