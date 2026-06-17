<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashlessController extends Controller
{
    public function index(): View
    {
        return view('cashless.index', [
            'userName' => session('auth_name', session('auth_username', 'Pengguna')),
            'stats' => $this->getStats(),
        ]);
    }

    public function saldo(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $rows = $this->getSaldoRows($search);

        return view('cashless.index', [
            'userName' => session('auth_name', session('auth_username', 'Pengguna')),
            'saldoRows' => $rows,
            'search' => $search,
            'activeTab' => 'saldo',
        ]);
    }

    public function topup(): View
    {
        return view('cashless.topup', [
            'userName' => session('auth_name', session('auth_username', 'Pengguna')),
        ]);
    }

    public function topupStore(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|string|max:50',
            'student_name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        if (!$this->tableExists('cashless_transactions')) {
            return back()->withErrors([
                'topup' => 'Tabel cashless_transactions belum ada. Silakan buat tabel dulu.',
            ])->withInput();
        }

        $amount = (float) $request->input('amount');
        $studentId = trim((string) $request->input('student_id'));
        $studentName = trim((string) $request->input('student_name'));
        $note = trim((string) $request->input('note', ''));
        $by = (string) session('auth_username', 'system');

        DB::table('cashless_transactions')->insert([
            'student_id' => $studentId,
            'student_name' => $studentName,
            'amount' => $amount,
            'type' => 'topup',
            'note' => $note,
            'created_by' => $by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->tableExists('cashless_wallets')) {
            $wallet = DB::table('cashless_wallets')->where('student_id', $studentId)->first();
            if ($wallet) {
                DB::table('cashless_wallets')->where('student_id', $studentId)->update([
                    'student_name' => $studentName,
                    'balance' => (float) ($wallet->balance ?? 0) + $amount,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('cashless_wallets')->insert([
                    'student_id' => $studentId,
                    'student_name' => $studentName,
                    'balance' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('cashless.transactions')->with('status', 'Topup berhasil disimpan.');
    }

    public function transactions(Request $request): View
    {
        $rows = collect();
        if ($this->tableExists('cashless_transactions')) {
            $rows = DB::table('cashless_transactions')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return view('cashless.transactions', [
            'userName' => session('auth_name', session('auth_username', 'Pengguna')),
            'rows' => $rows,
        ]);
    }

    private function getStats(): array
    {
        $totalWallet = 0;
        $totalBalance = 0;
        $todayTopup = 0;
        $totalTx = 0;

        if ($this->tableExists('cashless_wallets')) {
            $totalWallet = (int) DB::table('cashless_wallets')->count();
            $totalBalance = (float) DB::table('cashless_wallets')->sum('balance');
        }

        if ($this->tableExists('cashless_transactions')) {
            $totalTx = (int) DB::table('cashless_transactions')->count();
            $todayTopup = (float) DB::table('cashless_transactions')
                ->whereDate('created_at', now()->toDateString())
                ->where('type', 'topup')
                ->sum('amount');
        }

        return [
            'wallet_count' => $totalWallet,
            'total_balance' => $totalBalance,
            'today_topup' => $todayTopup,
            'transaction_count' => $totalTx,
        ];
    }

    private function getSaldoRows(string $search = ''): array
    {
        if (!$this->tableExists('cashless_wallets')) {
            return [];
        }

        $query = DB::table('cashless_wallets')
            ->select('student_id', 'student_name', 'balance', 'updated_at')
            ->orderBy('student_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('student_id', 'like', '%' . $search . '%')
                    ->orWhere('student_name', 'like', '%' . $search . '%');
            });
        }

        return $query->limit(200)->get()->map(fn ($r) => (array) $r)->all();
    }

    private function tableExists(string $table): bool
    {
        try {
            DB::table($table)->limit(1)->get();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

