<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\WaitlistSubscriberRepository;
use App\Service\WaitlistNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/admin/category')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'category' => $categoryRepository->findAll(),
            'productsCount' => $productRepository->count(),
        ]);
    }

    #[Route('/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_category_show', methods: ['GET'])]
    public function show(Category $category, WaitlistSubscriberRepository $waitlist): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
            'waitlistTotal' => $waitlist->count(),
        ]);
    }

    #[Route('/{id}/notify-waitlist', name: 'app_admin_category_notify', methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"notify" ~ args["category"].getId()'))]
    public function notifyWaitlist(Category $category, WaitlistNotifier $notifier): Response
    {
        if ($category->getWaitlistNotifiedAt()) {
            $this->addFlash('danger', 'La liste d\'attente a déjà été prévenue de ce drop.');
        } else {
            $sent = $notifier->announce($category);
            $this->addFlash('success', sprintf('%d e-mail(s) envoyé(s) à la liste d\'attente.', $sent));
        }

        return $this->redirectToRoute('app_admin_category_show', ['id' => $category->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
