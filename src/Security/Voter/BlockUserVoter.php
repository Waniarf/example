<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Security;

use function in_array;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Webmozart\Assert\Assert as WebmozartAssert;

class BlockUserVoter extends Voter
{
    public function __construct(private readonly Security $security, private readonly RoleHierarchyInterface $roleHierarchy)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        WebmozartAssert::isInstanceOf($subject, User::class);

        return $this->security->isGranted(User::ROLE_ADMIN) && !in_array(User::ROLE_ADMIN, $this->roleHierarchy->getReachableRoleNames($subject->getRoles()), true);
    }
}
