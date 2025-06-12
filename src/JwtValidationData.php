<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt;

// use Lcobucci\JWT\ValidationData;
use yii\base\Component;

/**
 * Class JwtValidationData.
 *
 * @author Sherin Bloemendaal <sherin@bloemendaal.dev>
 */
class JwtValidationData extends Component
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}
