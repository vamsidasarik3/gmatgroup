<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\SalesTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SalesTransactionController extends Controller
{
    /**
     * Display sales transactions list with participant and status filtering.
     */
    public function index(Request $request): View
    {
        $memberId = $request->input('member_id');
        $status = $request->input('status');
        $search = $request->input('search');

        $query = SalesTransaction::with(['member', 'commissionLedgerEntries'])
            ->latest('transaction_date');

        if ($memberId) {
            $query->where('member_id', $memberId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhere('reference_note', 'like', "%{$search}%");
            });
        }

        $sales = $query->paginate(15)->withQueryString();
        $members = Member::orderBy('name')->get();

        $totalVolume = SalesTransaction::sum('sale_amount');
        $completedCount = SalesTransaction::where('status', 'completed')->count();

        return view('admin.sales.index', [
            'sales' => $sales,
            'members' => $members,
            'selectedMemberId' => $memberId,
            'selectedStatus' => $status,
            'search' => $search,
            'totalVolume' => $totalVolume,
            'completedCount' => $completedCount,
        ]);
    }

    /**
     * Show form to create a new sales transaction.
     */
    public function create(): View
    {
        $members = Member::where('status', 'active')->orderBy('name')->get();
        $suggestedCode = 'TXN-' . date('Ym') . '-' . strtoupper(Str::random(4));

        return view('admin.sales.create', [
            'members' => $members,
            'suggestedCode' => $suggestedCode,
        ]);
    }

    /**
     * Store a validated sales transaction in the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_code' => ['required', 'string', 'max:100', 'unique:sales_transactions,transaction_code'],
            'member_id' => ['required', 'exists:members,id'],
            'sale_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'transaction_date' => ['required', 'date'],
            'status' => ['required', 'in:completed,pending,refunded,voided'],
            'reference_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sale = SalesTransaction::create([
            'transaction_code' => strtoupper($validated['transaction_code']),
            'member_id' => $validated['member_id'],
            'sale_amount' => $validated['sale_amount'],
            'transaction_date' => $validated['transaction_date'],
            'status' => $validated['status'],
            'reference_note' => $validated['reference_note'],
        ]);

        return redirect()->route('admin.sales.index')
            ->with('success', "Sale transaction {$sale->transaction_code} for $" . number_format($sale->sale_amount, 2) . " recorded successfully.");
    }

    /**
     * Display sale details and line-item commissions triggered.
     */
    public function show(SalesTransaction $sale): View
    {
        $sale->load(['member', 'commissionLedgerEntries.beneficiaryMember', 'commissionLedgerEntries.calculationRun']);

        return view('admin.sales.show', [
            'sale' => $sale,
        ]);
    }
}
