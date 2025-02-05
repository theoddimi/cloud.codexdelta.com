<?php

namespace Codexdelta\Libs\Exceptions;

enum ExceptionCase
{
    case INVALID_METHOD_400;
    case INVALID_ARGUMENT_400;
    case INVALID_PROPERTY_400;
    case TIMEOUT_400;

    case RESOURCE_NOT_FOUND_404;
    case MISSING_ENVIRONMENT_VARIABLE_500;

    public function fetch(?string $message): ExceptionDto
    {
        return match ($this) {
            self::INVALID_METHOD_400 => ExceptionDto::new($message ?? 'Invalid Method', 400),
            self::INVALID_ARGUMENT_400 => ExceptionDto::new($message ?? 'Invalid Argument', 400),
            self::INVALID_PROPERTY_400 => ExceptionDto::new($message ?? 'Invalid Property',  400),
            self::TIMEOUT_400 => ExceptionDto::new($message ?? 'Timeout', 400),
            self::RESOURCE_NOT_FOUND_404 => ExceptionDto::new($message ?? 'Not Found', 404),
            self::MISSING_ENVIRONMENT_VARIABLE_500 => ExceptionDto::new($message ?? 'Missing environment variable', 500),
        };
    }
}
