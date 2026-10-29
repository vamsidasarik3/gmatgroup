<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\Member;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display system settings, currency convention, decimal rounding policy, and database status.
     */
    public function index(): View
    {
        $dbStatus = [
            'database_name' => DB::connection()->getDatabaseName(),
            'driver' => DB::connection()->getDriverName(),
            'members_count' => Member::count(),
            'sales_count' => SalesTransaction::count(),
            'runs_count' => CalculationRun::count(),
            'ledger_count' => CommissionLedgerEntry::count(),
        ];

        return view('admin.settings.index', [
            'dbStatus' => $dbStatus,
        ]);
    }
}
