<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use App\Entity\User;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        // Check if password reset is required
        if ($user->isPasswordReset()) {
            // Redirect to a page where the user can reset their password
            return new RedirectResponse($this->urlGenerator->generate('password_reset'));
        }

        // Redirect based on user roles
        if ($this->isAdmin($token)) {
            // Als admin is stuur naar /admin
            return new RedirectResponse($this->urlGenerator->generate('login'));
        } else {
            // Redirect to user's profile if profile exists
            $profile = $user->getProfile();
            if ($profile) {
                // als user is stuur naar /profile/{id}
                return new RedirectResponse($this->urlGenerator->generate('profile_view', ['id' => $profile->getId()]));
            } else {
                throw new CustomUserMessageAuthenticationException('Profile not found for the user.');
            }
        }
    }

    private function isAdmin(TokenInterface $token): bool
    {
        // Check if the token has 'ROLE_ADMIN'
        return $token->getRoleNames() && in_array('ROLE_ADMIN', $token->getRoleNames());
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
