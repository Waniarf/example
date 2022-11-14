<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User\User;
use LogicException;
use Symfony\Component\Security\Core\Security as SecurityHelper;

/**
 * @final
 */
class Security
{
    private ?User $user = null;

    public function __construct(private readonly SecurityHelper $decorated)
    {
    }

    public function getUser(): User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $user = $this->decorated->getUser();

        if (!$user instanceof User) {
            throw new LogicException('User is not authenticated.'); // @codeCoverageIgnore
        }

        return $this->user = $user;
    }

    public function getOptionalUser(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $user = $this->decorated->getUser();

        if (!$user instanceof User && $user !== null) {
            throw new LogicException('User is not authenticated.'); // @codeCoverageIgnore
        }

        return $this->user = $user;
    }

    public function isGranted(mixed $attributes, mixed $subject = null): bool
    {
        return $this->decorated->isGranted($attributes, $subject);
    }
}
