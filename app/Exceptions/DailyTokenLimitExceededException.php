<?php

namespace App\Exceptions;

use RuntimeException;

class DailyTokenLimitExceededException extends RuntimeException
{
    public function __construct(
        protected int $dailyLimit,
        protected int $usedTokens,
        protected int $requestedTokens
    ) {
        $remainingTokens = max(0, $dailyLimit - $usedTokens);

        parent::__construct(
            'The application has reached its daily AI token budget. '
            . "Used: {$usedTokens}, requested: {$requestedTokens}, limit: {$dailyLimit}, remaining: {$remainingTokens}."
        );
    }

    public function getDailyLimit(): int
    {
        return $this->dailyLimit;
    }

    public function getUsedTokens(): int
    {
        return $this->usedTokens;
    }

    public function getRequestedTokens(): int
    {
        return $this->requestedTokens;
    }

    public function getRemainingTokens(): int
    {
        return max(0, $this->dailyLimit - $this->usedTokens);
    }
}
