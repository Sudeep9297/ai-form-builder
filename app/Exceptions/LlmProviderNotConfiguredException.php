<?php

namespace App\Exceptions;

use RuntimeException;

class LlmProviderNotConfiguredException extends RuntimeException
{
    public static function forGeneration(): self
    {
        return new self('No LLM provider is configured. Add an LLM API key to enable AI form generation.');
    }
}
