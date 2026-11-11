<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'] ?? null;
        $defaultRole = User::ROLE_USER;
        if ($user instanceof User) {
            if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
                $defaultRole = User::ROLE_ADMIN;
            } elseif (in_array(User::ROLE_STAFF, $user->getRoles(), true)) {
                $defaultRole = User::ROLE_STAFF;
            }
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'class' => 'admin-input',
                    'placeholder' => 'Enter username',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'admin-input',
                    'placeholder' => 'Enter email address',
                ],
            ])
            ->add('roleChoice', ChoiceType::class, [
                'label' => 'Assigned Role',
                'mapped' => false,
                'choices' => [
                    'Administrator (Full Access)' => User::ROLE_ADMIN,
                    'Staff Member (Limited Management)' => User::ROLE_STAFF,
                    'Regular Customer (Gamer)' => User::ROLE_USER,
                ],
                'data' => $defaultRole,
                'attr' => [
                    'class' => 'admin-select',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Reset Password (optional)' : 'Password',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'attr' => [
                    'class' => 'admin-input',
                    'placeholder' => $options['is_edit'] ? 'Leave blank to keep current password' : 'Enter secure password',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $options['is_edit'] ? [
                    new Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters.'),
                ] : [
                    new NotBlank(message: 'Please enter a password.'),
                    new Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
