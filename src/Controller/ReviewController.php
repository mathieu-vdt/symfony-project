<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review')]
class ReviewController extends AbstractController
{
    #[Route('/recipe/{id}/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $em,
        ReviewRepository $reviewRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if user already reviewed this recipe
        $existingReview = $reviewRepo->findByUserAndRecipe($user, $recipe);
        if ($existingReview) {
            $this->addFlash('warning', 'You have already reviewed this recipe.');
            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        // Check if user is trying to review their own recipe
        if ($recipe->getAuthor() === $user) {
            $this->addFlash('error', 'You cannot review your own recipe.');
            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        $review = new Review();
        $review->setRecipe($recipe);
        $review->setAuthor($user);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Your review has been posted!');
            return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Review $review,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if user owns this review
        if ($review->getAuthor() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own reviews.');
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Your review has been updated!');
            return $this->redirectToRoute('app_recipe_show', ['id' => $review->getRecipe()->getId()]);
        }

        return $this->render('review/edit.html.twig', [
            'form' => $form,
            'review' => $review,
            'recipe' => $review->getRecipe(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Review $review,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if user owns this review or is admin
        if ($review->getAuthor() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only delete your own reviews.');
        }

        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $recipeId = $review->getRecipe()->getId();
            $em->remove($review);
            $em->flush();

            $this->addFlash('success', 'Review deleted successfully.');
            return $this->redirectToRoute('app_recipe_show', ['id' => $recipeId]);
        }

        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('app_recipe_show', ['id' => $review->getRecipe()->getId()]);
    }
}