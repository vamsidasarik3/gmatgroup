<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateWeakestLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for Model 1: Weakest Link commission calculation.
     */
    public function rules(): array
    {
        return [
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'top_leader' => ['nullable', 'string', 'max:50'],
            'max_depth' => ['nullable', 'integer', 'min:1', 'max:100'],
            'strict_branching' => ['nullable', 'boolean'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.level' => ['required', 'integer', 'min:1'],
            'levels.*.commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.side_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.main_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.leader' => ['nullable', 'string', 'max:255'],
            'levels.*.main_person' => ['required', 'string', 'max:255'],
            'levels.*.main_sales' => ['nullable', 'numeric', 'min:0'],
            'levels.*.main_sale_id' => ['nullable', 'string', 'max:100'],
            'levels.*.side_person' => ['nullable', 'string', 'max:255'],
            'levels.*.side_sales' => ['nullable', 'numeric', 'min:0'],
            'levels.*.side_sale_id' => ['nullable', 'string', 'max:100'],
            'levels.*.additional_branches' => ['nullable', 'array'],
            'levels.*.additional_branches.*.person' => ['required_with:levels.*.additional_branches', 'string', 'max:255'],
            'levels.*.additional_branches.*.sales' => ['required_with:levels.*.additional_branches', 'numeric', 'min:0'],
            'levels.*.additional_branches.*.rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'levels.*.additional_branches.*.sale_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'commission_rate.required' => 'A default commission rate percentage is required.',
            'commission_rate.min' => 'Commission rate cannot be negative.',
            'commission_rate.max' => 'Commission rate cannot exceed 100.00%.',
            'levels.required' => 'At least one hierarchy level is required for calculation.',
            'levels.*.main_sales.min' => 'Sales amounts cannot be negative.',
            'levels.*.side_sales.min' => 'Sales amounts cannot be negative.',
        ];
    }
}
