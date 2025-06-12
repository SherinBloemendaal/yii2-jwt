<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt;

enum JwtSigner: string
{
    case HS256 = \Lcobucci\JWT\Signer\Hmac\Sha256::class;
    case HS384 = \Lcobucci\JWT\Signer\Hmac\Sha384::class;
    case HS512 = \Lcobucci\JWT\Signer\Hmac\Sha512::class;
    case ES256 = \Lcobucci\JWT\Signer\Ecdsa\Sha256::class;
    case ES384 = \Lcobucci\JWT\Signer\Ecdsa\Sha384::class;
    case ES512 = \Lcobucci\JWT\Signer\Ecdsa\Sha512::class;
    case RS256 = \Lcobucci\JWT\Signer\Rsa\Sha256::class;
    case RS384 = \Lcobucci\JWT\Signer\Rsa\Sha384::class;
    case RS512 = \Lcobucci\JWT\Signer\Rsa\Sha512::class;
}
