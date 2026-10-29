<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionModelController extends Controller
{
    /**
     * Display all commission models.
     */
    public function index(): View
    {
        $models = CommissionModel::withCount('calculationRuns')->get();

        return view('admin.commission_models.index', [
            'models' => $models,
        ]);
    }

    /**
     * Show form to edit and configure model-specific parameters.
     */
    public function edit(CommissionModel $commissionModel): View
    {
        return view('admin.commission_models.edit', [
            'model' => $commissionModel,
        ]);
    }

    /**
     * Update model configuration and model-specific settings.
     */
    public function update(Request $request, CommissionModel $commissionModel): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'number_of_levels' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_generations' => ['nullable', 'integer', 'min:1', 'max:20'],
            // Settings json fields
            'currency' => ['nullable', 'string', 'max:10'],
            'payout_threshold' => ['nullable', 'numeric', 'min:0'],
            'rounding_mode' => ['nullable', 'string', 'in:ROUND_HALF_UP,ROUND_FLOOR,ROUND_CEILING'],
            'minimum_leg_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $currentSettings = is_array($commissionModel->settings) ? $commissionModel->settings : [];

        $newSettings = array_merge($currentSettings, [
            'currency' => $validated['currency'] ?? 'USD',
            'payout_threshold' => (float) ($validated['payout_threshold'] ?? 10.00),
            'rounding_mode' => $validated['rounding_mode'] ?? 'ROUND_HALF_UP',
            'rate_convention' => 'percentage',
        ]);

        if ($commissionModel->model_type === 'weakest_link') {
            $newSettings['minimum_leg_count'] = (int) ($validated['minimum_leg_count'] ?? 2);
        } else {
            $newSettings['max_generations'] = (int) ($validated['max_generations'] ?? 5);
        }

        $commissionModel->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'commission_rate' => $validated['commission_rate'],
            'status' => $validated['status'],
            'number_of_levels' => $validated['number_of_levels'] ?? $commissionModel->number_of_levels,
            'max_generations' => $validated['max_generations'] ?? $commissionModel->max_generations,
            'settings' => $newSettings,
        ]);

        return redirect()->route('admin.commission-models.index')
            ->with('success', "Commission model '{$commissionModel->name}' successfully updated.");
    }

    /**
     * Quick toggle active status.
     */
    public function toggleStatus(CommissionModel $commissionModel): RedirectResponse
    {
        $newStatus = $commissionModel->status === 'active' ? 'inactive' : 'active';
        $commissionModel->update(['status' => $newStatus]);

        return back()->with('success', "Model '{$commissionModel->name}' status changed to {$newStatus}.");
    }
}
