<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt;

use Lcobucci\JWT\Builder as BuilderInterface;
use Lcobucci\JWT\ClaimsFormatter as ClaimsFormatterInterface;
use Lcobucci\JWT\Decoder as DecoderInterface;
use Lcobucci\JWT\Encoder as EncoderInterface;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Encoding\MicrosecondBasedDateConversion;
use Lcobucci\JWT\Encoding\UnifyAudience;
use Lcobucci\JWT\Parser as ParserInterface;
use Lcobucci\JWT\Signer as SignerInterface;
use Lcobucci\JWT\Signer\Key as KeyInterface;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token as TokenInterface;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Validation\Constraint as ConstraintInterface;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Validator as ValidatorInterface;
use sherinbloemendaal\jwt\Encoding\ChainedFormatter;
use yii\base\Component;
use yii\di\Instance;

/**
 * @property ParserInterface    $parser
 * @property ValidatorInterface $validator
 */
final class Jwt extends Component
{
    public BuilderInterface|string $builder = Builder::class;

    /**
     * @var EncoderInterface|array<string, mixed>|string
     */
    public EncoderInterface|array|string $encoder = JoseEncoder::class;

    /**
     * @var DecoderInterface|array<string, mixed>|string
     */
    public DecoderInterface|array|string $decoder = JoseEncoder::class;

    /**
     * @var ClaimsFormatterInterface|array<string, mixed>|string
     */
    public ClaimsFormatterInterface|array|string $claimsFormatter = [
        'class' => ChainedFormatter::class,
        'formatters' => [
            UnifyAudience::class,
            MicrosecondBasedDateConversion::class,
        ],
    ];

    public JwtSigner $signer = JwtSigner::HS256;

    public JwtKey $signerKey = JwtKey::EMPTY;

    public \Closure|string $signerKeyContents = '';

    public string $signerKeyPassphrase = '';

    /**
     * @var array<ConstraintInterface|\Closure(): ConstraintInterface|string|array<string, mixed>>
     */
    public array $constraints = [];

    public function getBuilder(): BuilderInterface
    {
        if (!$this->builder instanceof BuilderInterface) {
            $encoder = Instance::ensure($this->encoder, EncoderInterface::class);
            if (!$encoder instanceof EncoderInterface) {
                throw new \InvalidArgumentException('Encoder must implement EncoderInterface');
            }

            $claimsFormatter = Instance::ensure($this->claimsFormatter, ClaimsFormatterInterface::class);
            if (!$claimsFormatter instanceof ClaimsFormatterInterface) {
                throw new \InvalidArgumentException('Claims formatter must implement ClaimsFormatterInterface');
            }

            $this->builder = new Builder($encoder, $claimsFormatter);
        }

        return $this->builder;
    }

    public function getSigner(?JwtSigner $signer = null): SignerInterface
    {
        $signerClass = ($signer ?? $this->signer)->value;

        $signerInstance = Instance::ensure($signerClass, SignerInterface::class);
        if (!$signerInstance instanceof SignerInterface) {
            throw new \InvalidArgumentException('Signer must implement SignerInterface');
        }

        return $signerInstance;
    }

    public function getSignerKey(
        ?JwtKey $signerKey = null,
        ?string $contents = null,
        ?string $passphrase = null,
    ): KeyInterface {
        $signerKey ??= $this->signerKey;
        $contents ??= $this->resolveSignerKeyContents();
        $passphrase ??= $this->signerKeyPassphrase;

        return match ($signerKey) {
            JwtKey::EMPTY => InMemory::plainText('empty'),
            JwtKey::PLAIN_TEXT => InMemory::plainText($contents ?: 'default', $passphrase),
            JwtKey::BASE64_ENCODED => InMemory::base64Encoded($contents ?: 'ZGVmYXVsdA==', $passphrase),
            JwtKey::FILE => InMemory::file($contents ?: '/dev/null', $passphrase),
        };
    }

    private function resolveSignerKeyContents(): string
    {
        if ($this->signerKeyContents instanceof \Closure) {
            $result = ($this->signerKeyContents)();

            return \is_string($result) ? $result : '';
        }

        return $this->signerKeyContents;
    }

    public function getParser(): ParserInterface
    {
        static $parser = null;

        if (null === $parser) {
            $decoder = Instance::ensure($this->decoder, DecoderInterface::class);
            if (!$decoder instanceof DecoderInterface) {
                throw new \InvalidArgumentException('Decoder must implement DecoderInterface');
            }
            $parser = new Parser($decoder);
        }

        if (!$parser instanceof ParserInterface) {
            throw new \RuntimeException('Parser must implement ParserInterface');
        }

        return $parser;
    }

    public function parse(string $jwt): TokenInterface
    {
        if ('' === $jwt) {
            throw new InvalidTokenStructure('JWT string cannot be empty');
        }

        return $this->getParser()->parse($jwt);
    }

    public function getValidator(): ValidatorInterface
    {
        static $validator = null;

        if (null === $validator) {
            $validator = new Validator();
        }

        if (!$validator instanceof ValidatorInterface) {
            throw new \RuntimeException('Validator must implement ValidatorInterface');
        }

        return $validator;
    }

    public function validate(TokenInterface $token, ConstraintInterface ...$constraints): bool
    {
        return $this->getValidator()->validate($token, ...$constraints);
    }

    public function assert(TokenInterface $token, ConstraintInterface ...$constraints): void
    {
        $this->getValidator()->assert($token, ...$constraints);
    }

    public function loadToken(string $jwt, bool $validate = true, bool $throwException = true): ?TokenInterface
    {
        try {
            $token = $this->parse($jwt);
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $e) {
            if ($throwException) {
                throw $e;
            }

            \Yii::warning('Invalid JWT provided: '.$e->getMessage(), 'jwt');

            return null;
        }

        if ($validate && $this->constraints) {
            $constraints = [];
            foreach ($this->constraints as $constraint) {
                if ($constraint instanceof \Closure) {
                    $constraints[] = $constraint();
                } else {
                    $constraintInstance = Instance::ensure($constraint, ConstraintInterface::class);
                    if (!$constraintInstance instanceof ConstraintInterface) {
                        throw new \InvalidArgumentException('Constraint must implement ConstraintInterface');
                    }
                    $constraints[] = $constraintInstance;
                }
            }

            if ($throwException) {
                $this->assert($token, ...$constraints);
            } elseif (!$this->validate($token, ...$constraints)) {
                return null;
            }
        }

        return $token;
    }
}
