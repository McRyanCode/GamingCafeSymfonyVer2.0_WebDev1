<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_customers');
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('roleChoice')->getData();
            $user->setRoles([$role]);

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Account for "%s" created successfully with role [%s]!', $user->getUsername(), $role));
            return $this->redirectToRoute('admin_customers');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('roleChoice')->getData();
            $user->setRoles([$role]);

            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" updated successfully!', $user->getUsername()));
            return $this->redirectToRoute('admin_customers');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'You cannot delete your own admin account while logged in!');
            return $this->redirectToRoute('admin_customers');
        }

        if ($this->isCsrfTokenValid('delete_user_'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $username = $user->getUsername();
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Account "%s" has been removed successfully.', $username));
        } else {
            $this->addFlash('error', 'Invalid security token for user deletion.');
        }

        return $this->redirectToRoute('admin_customers');
    }
}
