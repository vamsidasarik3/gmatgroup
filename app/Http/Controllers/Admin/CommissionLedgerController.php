<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionLedgerController extends Controller
{
    /**
     * Display granular commission ledger entries with filters and pagination.
     */
    public function index(Request $request): View
    {
        $runId = $request->input('run_id');
        $beneficiaryId = $request->input('beneficiary_id');
        $generation = $request->input('generation');
        $status = $request->input('status');

        $query = CommissionLedgerEntry::with([
            'calculationRun',
            'commissionModel',
            'sourceSale',
            'sellerMember',
            'beneficiaryMember',
        ])->latest('id');

        if ($runId) {
            $query->where('calculation_run_id', $runId);
        }

        if ($beneficiaryId) {
            $query->where('beneficiary_member_id', $beneficiaryId);
        }

        if ($generation) {
            $query->where('generation', $generation);
        }

        if ($status) {
            if ($status === 'eligible') {
                $query->where('is_eligible', true);
            } elseif ($status === 'ineligible') {
                $query->where('is_eligible', false);
            } else {
                $query->where('payout_status', $status);
            }
        }

        $entries = $query->paginate(20)->withQueryString();

        $allRuns = CalculationRun::latest()->get();
        $allMembers = Member::orderBy('name')->get();

        $totalPayable = (float) CommissionLedgerEntry::where('is_eligible', true)->sum('commission_amount');
        $totalEntriesCount = CommissionLedgerEntry::count();

        return view('admin.ledger.index', [
            'entries' => $entries,
            'allRuns' => $allRuns,
            'allMembers' => $allMembers,
            'selectedRunId' => $runId,
            'selectedBeneficiaryId' => $beneficiaryId,
            'selectedGeneration' => $generation,
            'selectedStatus' => $status,
            'totalPayable' => $totalPayable,
            'totalEntriesCount' => $totalEntriesCount,
        ]);
    }
}
