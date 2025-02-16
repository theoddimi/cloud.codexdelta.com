<?php

namespace Codexdelta\App\Security;

use Codexdelta\App\Entities\User;
use Codexdelta\App\Http\Middleware\AuthMiddleware;
use Codexdelta\App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

class LoginAuthenticator
{
    public const LOGIN_ROUTE = '/login';
    public function supports(): bool
    {
        return strpos(request()->headers->get('referer'),  self::LOGIN_ROUTE)
            && request()->isMethod('POST');
    }

    public function checkCredentials($credentials, ?User $user): bool
    {
        if (null === $user) {
            return false;
        }

        if (!$this->supports()) {
            throw new \Exception('Unauthorized login route');
        }

        $factory = new PasswordHasherFactory([
            User::class => new NativePasswordHasher(),
        ]);
        $passwordHasher = new UserPasswordHasher($factory);

        return $passwordHasher->isPasswordValid($user, $credentials['password']);
    }

    public function getUser($credentials): ?User
    {
        $token = $credentials[CsrfToken::CSRF_TOKEN_KEY];

        if (!CsrfToken::isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        return UserRepository::get()->findOneBy(['email' => $credentials['email']]);
    }
}
