<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/connexion', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $users, AuthService $auth): Response
    {
        return $this->processLogin($request, $users, $auth, false);
    }

    #[Route('/organisateur/connexion', name: 'organizer_login', methods: ['GET', 'POST'])]
    public function organizerLogin(Request $request, UserRepository $users, AuthService $auth): Response
    {
        return $this->processLogin($request, $users, $auth, true);
    }

    #[Route('/inscription', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserRepository $users, EntityManagerInterface $em, AuthService $auth): Response
    {
        if ($auth->getUser($request) !== null) {
            return $this->redirectToRoute('account');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            if ($users->findOneByEmail($user->getEmail()) !== null) {
                $error = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                $user->setPassword(password_hash((string) $form->get('password')->getData(), PASSWORD_DEFAULT));
                $em->persist($user);
                $em->flush();
                $auth->login($request, $user);

                return $this->redirect($auth->consumeTarget($request, $this->generateUrl('account')));
            }
        }

        return $this->render('user/register.html.twig', ['form' => $form, 'error' => $error]);
    }

    #[Route('/deconnexion', name: 'logout', methods: ['POST'])]
    public function logout(Request $request, AuthService $auth): Response
    {
        if (!$this->isCsrfTokenValid('logout', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $auth->logout($request);

        return $this->redirectToRoute('home');
    }

    private function processLogin(Request $request, UserRepository $users, AuthService $auth, bool $organizerOnly): Response
    {
        $currentUser = $auth->getUser($request);
        if ($currentUser !== null && (!$organizerOnly || $currentUser->isOrganizer())) {
            return $this->redirect($auth->consumeTarget($request, $this->generateUrl($organizerOnly ? 'organizer_dashboard' : 'account')));
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
        $error = $currentUser !== null && $organizerOnly
            ? 'Votre compte utilisateur n’a pas accès au back-office organisateur. Connectez-vous avec un compte organisateur.'
            : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $users->findOneByEmail($data['email']);

            if ($user === null || !password_verify($data['password'], $user->getPassword())) {
                $error = $organizerOnly ? 'Identifiants organisateur incorrects.' : 'Adresse e-mail ou mot de passe incorrect.';
            } elseif ($organizerOnly && !$user->isOrganizer()) {
                $error = 'Ce compte ne possède pas les droits organisateur.';
            } else {
                $auth->login($request, $user);

                return $this->redirect($auth->consumeTarget($request, $this->generateUrl($organizerOnly ? 'organizer_dashboard' : 'account')));
            }
        }

        return $this->render('user/login.html.twig', [
            'form' => $form,
            'error' => $error,
            'organizerLogin' => $organizerOnly,
        ]);
    }
}
