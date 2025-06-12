# Yii2 JWT

<div align="center">

![Packagist Version](https://img.shields.io/packagist/v/sherinbloemendaal/yii2-jwt?style=for-the-badge&logo=packagist)
![CI Status](https://img.shields.io/github/actions/workflow/status/sherinbloemendaal/yii2-jwt/ci.yml?branch=main&style=for-the-badge&logo=github)
![License](https://img.shields.io/packagist/l/sherinbloemendaal/yii2-jwt?style=for-the-badge)
![PHP Version](https://img.shields.io/packagist/php-v/sherinbloemendaal/yii2-jwt?style=for-the-badge&logo=php)

**🔐 Modern, type-safe JWT for Yii2**

*Seamless [lcobucci/jwt](https://github.com/lcobucci/jwt) 5.x integration for Yii 2.0 with PHP 8.1+ support*

[Installation](#-installation) •
[Quick Start](#-quick-start) •
[Examples](#-examples) •
[API Reference](#-api-reference) •
[Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [✨ Features](#-features)
- [📋 Requirements](#-requirements)
- [📦 Installation](#-installation)
- [🚀 Quick Start](#-quick-start)
- [🔧 Configuration](#-configuration)
- [💡 Examples](#-examples)
- [🔑 Supported Algorithms](#-supported-algorithms)
- [📚 API Reference](#-api-reference)
- [🛠 Troubleshooting](#-troubleshooting)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)

---

## ✨ Features

- 🔒 **Secure JWT Operations** - Create, parse, and validate JWT tokens with industry-standard security
- 🧩 **Modern PHP** - Native PHP 8.1+ types, enums, and attributes
- 🛡️ **Multiple Algorithms** - HMAC, RSA, ECDSA support with easy configuration
- ⚡ **Yii2 Integration** - Seamless component integration with dependency injection
- 🧑‍💻 **Developer Friendly** - Clean, IDE-friendly API with full type hints
- 🔄 **Flexible Configuration** - Support for various key formats and validation constraints
- 🌐 **REST Ready** - Built-in HTTP Bearer authentication filter

---

## 📋 Requirements

- ![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php) PHP 8.1 or higher
- ![Yii2](https://img.shields.io/badge/Yii2-2.0.52+-0073E6?style=flat) Yii2 ^2.0.52
- ![JWT](https://img.shields.io/badge/lcobucci/jwt-5.0+-000000?style=flat) lcobucci/jwt ^5.0

---

## 📦 Installation

Install via Composer:

```bash
composer require sherinbloemendaal/yii2-jwt
```

---

## 🚀 Quick Start

### 1. Configure the Component

Add to your Yii2 application configuration:

```php
'components' => [
    'jwt' => [
        'class' => \sherinbloemendaal\jwt\Jwt::class,
        'signer' => \sherinbloemendaal\jwt\JwtSigner::HS256,
        'signerKey' => \sherinbloemendaal\jwt\JwtKey::PLAIN_TEXT,
        'signerKeyContents' => getenv('JWT_SECRET') ?: 'your-secret-key',
        'constraints' => [
            static fn() => new \Lcobucci\JWT\Validation\Constraint\LooseValidAt(
                \Lcobucci\Clock\SystemClock::fromUTC()
            ),
        ],
    ],
],
```

### 2. Create Your First Token

```php
/** @var \sherinbloemendaal\jwt\Jwt $jwt */
$jwt = Yii::$app->jwt;
$now = new DateTimeImmutable();

$token = $jwt->getBuilder()
    ->issuedBy('https://your-app.com')           // iss claim
    ->permittedFor('https://api.your-app.com')   // aud claim
    ->identifiedBy('unique-token-id')            // jti claim
    ->issuedAt($now)                            // iat claim
    ->expiresAt($now->modify('+1 hour'))        // exp claim
    ->withClaim('uid', 42)                      // custom claim
    ->withClaim('role', 'admin')                // another custom claim
    ->getToken($jwt->getSigner(), $jwt->getSignerKey());

echo $token->toString();
```

### 3. Parse and Validate Token

```php
$tokenString = '...'; // JWT token from request

try {
    $token = $jwt->loadToken($tokenString, validate: true);
    
    // Access claims
    $userId = $token->claims()->get('uid');
    $userRole = $token->claims()->get('role');
    
    echo "Welcome user #{$userId} with role: {$userRole}";
} catch (\Exception $e) {
    echo "Invalid token: " . $e->getMessage();
}
```

---

## 🔧 Configuration

### Basic Configuration Options

```php
'jwt' => [
    'class' => \sherinbloemendaal\jwt\Jwt::class,
    
    // Signing algorithm
    'signer' => \sherinbloemendaal\jwt\JwtSigner::HS256,
    
    // Key configuration
    'signerKey' => \sherinbloemendaal\jwt\JwtKey::PLAIN_TEXT,
    'signerKeyContents' => 'your-secret-key',
    'signerKeyPassphrase' => '', // for encrypted keys
    
    // Validation constraints
    'constraints' => [
        // Validate issued/expires dates
        static fn() => new \Lcobucci\JWT\Validation\Constraint\LooseValidAt(
            \Lcobucci\Clock\SystemClock::fromUTC()
        ),
        // Validate issuer
        static fn() => new \Lcobucci\JWT\Validation\Constraint\IssuedBy('https://your-app.com'),
        // Validate audience
        static fn() => new \Lcobucci\JWT\Validation\Constraint\PermittedFor('https://api.your-app.com'),
    ],
],
```

### Advanced Configuration

```php
'jwt' => [
    'class' => \sherinbloemendaal\jwt\Jwt::class,
    
    // Custom encoder/decoder
    'encoder' => ['class' => \Lcobucci\JWT\Encoding\JoseEncoder::class],
    'decoder' => ['class' => \Lcobucci\JWT\Encoding\JoseEncoder::class],
    
    // Custom claims formatter
    'claimsFormatter' => [
        'class' => \sherinbloemendaal\jwt\Encoding\ChainedFormatter::class,
        'formatters' => [
            \Lcobucci\JWT\Encoding\UnifyAudience::class,
            \Lcobucci\JWT\Encoding\MicrosecondBasedDateConversion::class,
        ],
    ],
],
```

---

## 💡 Examples

### REST API Authentication

Create a secure REST controller with JWT authentication:

```php
<?php

use yii\rest\Controller;
use sherinbloemendaal\jwt\JwtHttpBearerAuth;

class ApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'optional' => ['login', 'public-endpoint'],
        ];
        return $behaviors;
    }

    public function actionLogin(): array
    {
        // Authenticate user credentials here...
        $user = $this->authenticateUser();
        
        if (!$user) {
            throw new \yii\web\UnauthorizedHttpException('Invalid credentials');
        }

        $jwt = Yii::$app->jwt;
        $now = new DateTimeImmutable();
        
        $token = $jwt->getBuilder()
            ->issuedBy(Yii::$app->request->hostInfo)
            ->permittedFor(Yii::$app->request->hostInfo . '/api')
            ->identifiedBy(uniqid())
            ->issuedAt($now)
            ->expiresAt($now->modify('+8 hours'))
            ->withClaim('uid', $user->id)
            ->withClaim('email', $user->email)
            ->withClaim('role', $user->role)
            ->getToken($jwt->getSigner(), $jwt->getSignerKey());

        return [
            'access_token' => $token->toString(),
            'token_type' => 'Bearer',
            'expires_in' => 28800, // 8 hours in seconds
        ];
    }

    public function actionProfile(): array
    {
        $token = Yii::$app->request->getHeaders()->get('authorization');
        $parsedToken = Yii::$app->jwt->loadToken(str_replace('Bearer ', '', $token));
        
        return [
            'uid' => $parsedToken->claims()->get('uid'),
            'email' => $parsedToken->claims()->get('email'),
            'role' => $parsedToken->claims()->get('role'),
        ];
    }
}
```

### Working with RSA Keys

```php
'jwt' => [
    'class' => \sherinbloemendaal\jwt\Jwt::class,
    'signer' => \sherinbloemendaal\jwt\JwtSigner::RS256,
    'signerKey' => \sherinbloemendaal\jwt\JwtKey::FILE,
    'signerKeyContents' => '/path/to/private-key.pem',
    'signerKeyPassphrase' => 'optional-passphrase',
],
```

### Custom Validation Constraints

```php
'jwt' => [
    'class' => \sherinbloemendaal\jwt\Jwt::class,
    'constraints' => [
        // Time-based validation with leeway
        static fn() => new \Lcobucci\JWT\Validation\Constraint\LooseValidAt(
            \Lcobucci\Clock\SystemClock::fromUTC(),
            new DateInterval('PT30S') // 30 second leeway
        ),
        
        // Custom constraint
        static function() {
            return new class implements \Lcobucci\JWT\Validation\Constraint {
                public function assert(\Lcobucci\JWT\Token $token): void
                {
                    if (!$token->claims()->has('uid')) {
                        throw new \Lcobucci\JWT\Validation\ConstraintViolation('Token must contain uid claim');
                    }
                }
            };
        },
    ],
],
```

### Refresh Token Pattern

```php
class AuthService
{
    public function generateTokenPair(int $userId): array
    {
        $jwt = Yii::$app->jwt;
        $now = new DateTimeImmutable();
        
        // Short-lived access token
        $accessToken = $jwt->getBuilder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+15 minutes'))
            ->withClaim('uid', $userId)
            ->withClaim('type', 'access')
            ->getToken($jwt->getSigner(), $jwt->getSignerKey());
        
        // Long-lived refresh token
        $refreshToken = $jwt->getBuilder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+7 days'))
            ->withClaim('uid', $userId)
            ->withClaim('type', 'refresh')
            ->getToken($jwt->getSigner(), $jwt->getSignerKey());
        
        return [
            'access_token' => $accessToken->toString(),
            'refresh_token' => $refreshToken->toString(),
            'expires_in' => 900, // 15 minutes
        ];
    }
}
```

---

## 🔑 Supported Algorithms

| Enum Value            | Algorithm     | Key Type Required |
|----------------------|---------------|-------------------|
| `JwtSigner::HS256`   | HMAC-SHA-256  | Symmetric         |
| `JwtSigner::HS384`   | HMAC-SHA-384  | Symmetric         |
| `JwtSigner::HS512`   | HMAC-SHA-512  | Symmetric         |
| `JwtSigner::RS256`   | RSA-SHA-256   | RSA Private       |
| `JwtSigner::RS384`   | RSA-SHA-384   | RSA Private       |
| `JwtSigner::RS512`   | RSA-SHA-512   | RSA Private       |
| `JwtSigner::ES256`   | ECDSA-SHA-256 | ECDSA Private     |
| `JwtSigner::ES384`   | ECDSA-SHA-384 | ECDSA Private     |
| `JwtSigner::ES512`   | ECDSA-SHA-512 | ECDSA Private     |

### Key Format Options

| Enum Value                | Description                    |
|--------------------------|--------------------------------|
| `JwtKey::EMPTY`          | Empty key (for testing only)  |
| `JwtKey::PLAIN_TEXT`     | Plain text key                 |
| `JwtKey::BASE64_ENCODED` | Base64 encoded key             |
| `JwtKey::FILE`           | Key loaded from file           |

---

## 📚 API Reference

### Main JWT Component

#### `getBuilder(): BuilderInterface`
Returns a token builder instance for creating new tokens.

#### `getSigner(?JwtSigner $signer = null): SignerInterface`
Returns the configured signer instance.

#### `getSignerKey(?JwtKey $signerKey = null, ?string $contents = null, ?string $passphrase = null): KeyInterface`
Returns the configured signing key.

#### `parse(string $jwt): TokenInterface`
Parses a JWT string into a token object.

#### `validate(TokenInterface $token, ConstraintInterface ...$constraints): bool`
Validates a token against the given constraints.

#### `loadToken(string $jwt, bool $validate = true, bool $throwException = true): ?TokenInterface`
Convenience method to parse and optionally validate a token.

---

## 🛠 Troubleshooting

### Common Issues

**"Invalid token structure"**
- Ensure the JWT string is properly formatted (header.payload.signature)
- Check that the token hasn't been corrupted during transmission

**"Token signature mismatch"**
- Verify that the same signing key is used for both creation and validation
- Ensure the signing algorithm matches between creation and validation

**"Token has expired"**
- Check the `exp` claim in your token
- Consider adding leeway to your validation constraints

**"Invalid key format"**
- For RSA/ECDSA keys, ensure they are in PEM format
- Check file permissions if loading keys from files

### Debug Mode

Enable debug logging by configuring Yii2's logger:

```php
'log' => [
    'targets' => [
        [
            'class' => 'yii\log\FileTarget',
            'categories' => ['jwt'],
            'logFile' => '@runtime/logs/jwt.log',
        ],
    ],
],
```

### Performance Tips

- Use static caching for parser and validator instances (already implemented)
- Consider using Redis for token blacklisting in production
- Use appropriate token expiration times to balance security and performance

---

## 🤝 Contributing

We welcome contributions! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check code style: `composer cs-check`

### Guidelines

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation as needed
- Keep backward compatibility in mind

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

<div align="center">

**Made with ❤️ for the Yii2 community**

[Report Bug](../../issues) • [Request Feature](../../issues) • [Documentation](../../wiki)

</div>
