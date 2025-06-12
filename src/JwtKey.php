<?php

declare(strict_types=1);

namespace sherinbloemendaal\jwt;

enum JwtKey: int
{
    case EMPTY = 0;
    case PLAIN_TEXT = 1;
    case BASE64_ENCODED = 2;
    case FILE = 3;
}
