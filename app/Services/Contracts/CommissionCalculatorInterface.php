<?php

namespace App\Services\Contracts;

interface CommissionCalculatorInterface
{
    /**
     * Get the identifier of the commission model type (e.g. 'weakest_link', 'generation_override').
     */
    public function getModelType(): string;

    /**
     * Compute individual commission from sale amount and percentage rate.
     */
    public function calculateIndividualCommission(float|int|string $sales, float|int|string $rate): float;

    /**
     * Execute standard calculation for the model.
     *
     * @param  array  $hierarchyData  Structure defining levels or network edges
     * @param  array  $salesData      Sales records or level sales
     * @param  array  $options        Model configuration parameters
     * @return array Standardized structured calculation result
     */
    public function execute(array $hierarchyData, array $salesData = [], array $options = []): array;
}
