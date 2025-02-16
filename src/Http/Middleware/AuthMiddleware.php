<?php

namespace Codexdelta\App\Http\Middleware;

use Codexdelta\Libs\Http\CdxRequest;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthMiddleware
{
    public const NAME = 'auth';
    public const REDIRECT_FOR_UNAUTHENTICATED = '/login';

    public function apply(CdxRequest $request): bool|RedirectResponse
    {
        $session = $request->getSession();

        if (!is_null($request->user()) && !is_null($session->get('auth_token'))) {
            if (
                !$request->cookies->has('auth_token')
                || $request->cookies->get('auth_token') !== $session->get('auth_token')
            ) {
                $session->invalidate();
                $response = new RedirectResponse(self::REDIRECT_FOR_UNAUTHENTICATED);
                $response->headers->setCookie(new Cookie('auth_token', ''));

                return $response;
            }

            // Last activity max expiration
            $lastActivity = $session->get('last_activity', time());
            $timeout = $session->get('session_expiration', 1800);

            if ((time() - $lastActivity) > $timeout) {
                // Destroy session and redirect to login if expired
                $session->invalidate();
                $response = new RedirectResponse(self::REDIRECT_FOR_UNAUTHENTICATED);
                $response->headers->setCookie(new Cookie('auth_token', ''));

                return $response;
            }

            // Update last activity time if session is still valid
            $session->set('last_activity', time());
            $session->set('session_expiration', 1800);
        } else {
            return new RedirectResponse(self::REDIRECT_FOR_UNAUTHENTICATED);
        }

        return true;
    }
}