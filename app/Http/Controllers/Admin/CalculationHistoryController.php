<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalculationHistoryController extends Controller
{
    /**
     * Display all calculation runs with status and metrics.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $query = CalculationRun::with('commissionModel')
            ->latest('id');

        if ($status) {
            $query->where('status', $status);
        }

        $runs = $query->paginate(15)->withQueryString();

        $completedCount = CalculationRun::where('status', 'completed')->count();
        $failedCount = CalculationRun::where('status', 'failed')->count();
        $totalCalculated = (float) CalculationRun::where('status', 'completed')->sum('total_commission_payable');

        return view('admin.history.index', [
            'runs' => $runs,
            'selectedStatus' => $status,
            'completedCount' => $completedCount,
            'failedCount' => $failedCount,
            'totalCalculated' => $totalCalculated,
        ]);
    }

    /**
     * Detailed inspection of inputs, settings snapshot, and ledger results for a run.
     */
    public function show(CalculationRun $calculationRun): View
    {
        $calculationRun->load('commissionModel');

        $ledgerEntries = CommissionLedgerEntry::with(['beneficiaryMember', 'sellerMember', 'sourceSale'])
            ->where('calculation_run_id', $calculationRun->id)
            ->paginate(25);

        return view('admin.history.show', [
            'run' => $calculationRun,
            'ledgerEntries' => $ledgerEntries,
        ]);
    }
}
