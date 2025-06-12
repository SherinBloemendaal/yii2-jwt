<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt\Encoding;

use Lcobucci\JWT\ClaimsFormatter as ClaimsFormatterInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter as JwtEncodingChainedFormatter;
use yii\base\Component;
use yii\di\Instance;

final class ChainedFormatter extends Component implements ClaimsFormatterInterface
{
    private JwtEncodingChainedFormatter $chainedFormatter;

    /**
     * @var array<string|array<string, mixed>>
     */
    public array $formatters;

    public function init(): void
    {
        parent::init();

        $formatters = [];
        foreach ($this->formatters as $f) {
            $formatter = Instance::ensure($f, ClaimsFormatterInterface::class);
            if (!$formatter instanceof ClaimsFormatterInterface) {
                throw new \InvalidArgumentException('Formatter must implement ClaimsFormatterInterface');
            }
            $formatters[] = $formatter;
        }

        $this->chainedFormatter = new JwtEncodingChainedFormatter(...$formatters);
    }

    /**
     * @param array<mixed> $params
     */
    public function __call($name, $params): mixed
    {
        if (!method_exists($this->chainedFormatter, $name)) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method %s::%s()', self::class, $name));
        }

        return $this->chainedFormatter->{$name}(...$params);
    }

    /**
     * @param array<non-empty-string, mixed> $claims
     *
     * @return array<non-empty-string, mixed>
     */
    public function formatClaims(array $claims): array
    {
        return $this->chainedFormatter->formatClaims($claims);
    }
}
