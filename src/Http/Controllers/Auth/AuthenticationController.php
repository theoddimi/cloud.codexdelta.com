<?php

namespace Codexdelta\App\Http\Controllers\Auth;

use Codexdelta\App\Http\Middleware\AuthMiddleware;
use Codexdelta\App\Security\LoginAuthenticator;
use Codexdelta\Libs\Http\CdxSession;
use Codexdelta\Libs\HttpApi\Response;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthenticationController
{
    public function login()
    {
        // Dummy user database
        $csrfToken = bin2hex(random_bytes(32));
        session()->set('csrf_token', $csrfToken);
        return view('auth/login.twig', ['csrf' => $csrfToken]);
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    public function authenticate()
    {
        $requestData = request()->request->all();
//        $username = $requestData['email'];

        $authenticator = new LoginAuthenticator();

        if ($authenticator->checkCredentials($requestData, $authenticator->getUser($requestData))) {
            $session = request()->getSession();
            $session->set(
                'auth_user',
                 'user_' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'),1, 10)
            );
            $session->migrate(true);
            $session->set('last_activity', time());
            $session->set('session_expiration', 1800);

            // Generate a random auth token
            $authToken = bin2hex(random_bytes(32));
            $session->set('auth_token', $authToken);

            $response = new RedirectResponse('/welcome');
            $response->headers->setCookie(
                new Cookie(
                    'auth_token',
                    $authToken,
                    time() + 7200,
                    '/',
                    '',
                    false,
                    true,false,
                    'Strict')
            );
            $response->send();
        } else {
            session()->setFlashMessage('globalMessage', 'Wrong credentials. Please try again');
            $response = new RedirectResponse(AuthMiddleware::REDIRECT_FOR_UNAUTHENTICATED);
            $response->send();
        }
    }

    public function logout(): RedirectResponse|Response
    {
        if (request()->user()) {
            session()->invalidate();
            $response = new RedirectResponse(AuthMiddleware::REDIRECT_FOR_UNAUTHENTICATED);
            $response->headers->setCookie(new Cookie('auth_token', '', time() + 3600));

            return $response->send();
        }

        $response = new RedirectResponse(AuthMiddleware::REDIRECT_FOR_UNAUTHENTICATED);

        return $response->send();
    }
}