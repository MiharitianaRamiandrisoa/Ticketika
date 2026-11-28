<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/categories')]
final class CategoryController extends AbstractController
{
    #[Route('', name: 'organizer_categories', methods: ['GET', 'POST'])]
    public function index(Request $request, CategoryRepository $categories, EntityManagerInterface $em, AuthService $auth): Response
    {
        if (!$this->checkOrganizer($request, $auth)) {
            $user = $auth->getUser($request);
            if ($user === null) {
                $auth->rememberTarget($request, $request->getRequestUri());

                return $this->redirectToRoute('organizer_login');
            }

            return $this->redirectToRoute('organizer_login');
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(Category::slugify($category->getName()));
            if ($category->getSlug() === '' || $categories->findOneBy(['slug' => $category->getSlug()]) !== null) {
                $error = 'Cette catégorie existe déjà ou son nom est invalide.';
            } else {
                $em->persist($category);
                $em->flush();
                $this->addFlash('success', 'Catégorie ajoutée.');

                return $this->redirectToRoute('organizer_categories');
            }
        }

        return $this->render('organizer/categories.html.twig', [
            'categories' => $categories->findBy([], ['name' => 'ASC']),
            'form' => $form,
            'error' => $error,
        ]);
    }

    #[Route('/{id}/toggle', name: 'organizer_category_toggle', methods: ['POST'])]
    public function toggle(Category $category, Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        if (!$this->checkOrganizer($request, $auth)) {
            $user = $auth->getUser($request);
            if ($user === null) {
                $auth->rememberTarget($request, $request->getRequestUri());

                return $this->redirectToRoute('organizer_login');
            }

            return $this->redirectToRoute('organizer_login');
        }
        if ($this->isCsrfTokenValid('category-toggle-'.$category->getId(), $request->request->getString('_token'))) {
            $category->setActive(!$category->isActive());
            $em->flush();
        }

        return $this->redirectToRoute('organizer_categories');
    }

    private function checkOrganizer(Request $request, AuthService $auth): bool
    {
        return ($auth->getUser($request)?->isOrganizer()) === true;
    }
}
