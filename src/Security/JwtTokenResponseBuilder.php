<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class JwtTokenResponseBuilder
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function build(User\User $user, int $statusCode): JsonResponse
    {
        $response = new JsonResponse();
        $token = $this->jwtManager->create($user);

        $authenticationSuccessEvent = new AuthenticationSuccessEvent(['token' => $token], $user, $response);

        /*
         * Dispatch this event to trigger all listeners that adds extra data for JWT Token Response:
         *  - user id
         *  - refresh token
         *  - expires_in and so on
         */
        $this->eventDispatcher->dispatch($authenticationSuccessEvent, Events::AUTHENTICATION_SUCCESS);

        $response->setData($authenticationSuccessEvent->getData());
        $response->setStatusCode($statusCode);

        return $response;
    }
}
