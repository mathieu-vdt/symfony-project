<?php

namespace App\Security\Voter;

use App\Entity\Recipe;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class RecipeVoter extends Voter
{
    public const VIEW   = 'RECIPE_VIEW';
    public const CREATE = 'RECIPE_CREATE';
    public const EDIT   = 'RECIPE_EDIT';
    public const DELETE = 'RECIPE_DELETE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true)) {
            return false;
        }

        // CREATE has no subject; others require a Recipe
        if ($attribute === self::CREATE) {
            return true;
        }

        return $subject instanceof Recipe;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false; // not logged-in
        }

        // Full access for admins (respects hierarchy)
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // everyone logged-in can view

            case self::CREATE:
                // any student or moderator (hierarchy-aware)
                return $this->security->isGranted('ROLE_STUDENT')
                    || $this->security->isGranted('ROLE_MODERATOR');

            case self::EDIT:
                // moderators can edit all
                if ($this->security->isGranted('ROLE_MODERATOR')) {
                    return true;
                }
                /** @var Recipe $recipe */
                $recipe = $subject;
                return $this->isOwner($user, $recipe);

            case self::DELETE:
                /** @var Recipe $recipe */
                $recipe = $subject;
                // moderators: delete only own (admins already handled)
                if ($this->security->isGranted('ROLE_MODERATOR')) {
                    return $this->isOwner($user, $recipe);
                }
                return $this->isOwner($user, $recipe);
        }

        return false;
    }

    private function isOwner(User $user, Recipe $recipe): bool
    {
        $author = $recipe->getAuthor();
        if (!$author) {
            return false;
        }
        return (string) $author->getId() === (string) $user->getId();
    }
}
