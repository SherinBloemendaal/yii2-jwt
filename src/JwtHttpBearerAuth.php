<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt;

use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use yii\di\Instance;
use yii\filters\auth\AuthMethod;
use yii\web\IdentityInterface;

/**
 * Action filter for JWT-based HTTP Bearer authentication.
 *
 * Attach as a behavior to a controller or module:
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'bearerAuth' => [
 *             'class' => \sherinbloemendaal\jwt\JwtHttpBearerAuth::class,
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Sherin Bloemendaal <sherin@bloemendaal.dev>
 */
class JwtHttpBearerAuth extends AuthMethod
{
    /**
     * @var Jwt|string|array<string, mixed>
     */
    public Jwt|string|array $jwt = 'jwt';

    public string $header = 'Authorization';

    public string $realm = 'api';

    public string $schema = 'Bearer';

    /**
     * @var (callable(Token, string): mixed)|null
     */
    public $auth;

    private Jwt $jwtComponent;

    public function init(): void
    {
        parent::init();
        $jwtInstance = Instance::ensure($this->jwt, Jwt::class);
        if (!$jwtInstance instanceof Jwt) {
            throw new \InvalidArgumentException('JWT component must be an instance of Jwt class');
        }
        $this->jwtComponent = $jwtInstance;
    }

    public function authenticate($user, $request, $response): ?IdentityInterface
    {
        $authHeader = $request->getHeaders()->get($this->header);
        $authHeaderValue = \is_array($authHeader) ? ($authHeader[0] ?? '') : ($authHeader ?? '');

        if (\is_string($authHeaderValue) && '' !== $authHeaderValue && preg_match('/^'.$this->schema.'\s+(.*?)$/', $authHeaderValue, $matches)) {
            try {
                $token = $this->jwtComponent->loadToken($matches[1]);
                if (null === $token) {
                    return null;
                }
            } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $e) {
                return null;
            }

            if ($this->auth) {
                $identity = ($this->auth)($token, static::class);

                return $identity instanceof IdentityInterface ? $identity : null;
            }
            $identity = $user->loginByAccessToken($token->toString(), static::class);

            return $identity instanceof IdentityInterface ? $identity : null;
        }

        return null;
    }

    public function challenge($response): void
    {
        $response->getHeaders()->set(
            'WWW-Authenticate',
            "{$this->schema} realm=\"{$this->realm}\", error=\"invalid_token\", error_description=\"The access token invalid or expired\""
        );
    }
}
