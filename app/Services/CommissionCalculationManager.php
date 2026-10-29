<?php

namespace App\Services;

use App\Services\Contracts\CommissionCalculatorInterface;
use InvalidArgumentException;

class CommissionCalculationManager
{
    public function __construct(
        protected WeakestLinkCommissionService $weakestLinkService,
        protected LevelOverrideCommissionService $overrideService
    ) {}

    /**
     * Resolve the calculator engine by model type.
     */
    public function getCalculator(string $modelType): CommissionCalculatorInterface
    {
        return match ($modelType) {
            'weakest_link', 'model_1' => $this->weakestLinkService,
            'generation_override', 'override', 'model_2' => $this->overrideService,
            default => throw new InvalidArgumentException("Unsupported commission model type: '{$modelType}'."),
        };
    }
}
