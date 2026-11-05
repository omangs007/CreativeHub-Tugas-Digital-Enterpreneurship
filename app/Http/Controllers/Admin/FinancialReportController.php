<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinancialReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan keuangan admin. Halaman, cetak, dan PDF memakai FinancialReport yang sama
 * sehingga angka selalu konsisten dengan filter yang dipilih.
 */
class FinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        $report = $this->report($request);

        return view('admin.financial-report.index', [
            'report' => $report,
            'summary' => $report->summary(),
            'transactions' => $report->transactions(),
            'presets' => FinancialReport::PRESETS,
            'printMode' => $request->boolean('print'),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $report = $this->report($request);

        return Pdf::loadView('admin.financial-report.pdf', [
            'report' => $report,
            'summary' => $report->summary(),
            'transactions' => $report->transactions(),
            'printedAt' => now(),
            'printedBy' => $request->user()->name,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('laporan-keuangan-'.$report->start->format('Ymd').'-'.$report->end->format('Ymd').'.pdf');
    }

    private function report(Request $request): FinancialReport
    {
        $input = $request->validate([
            'preset' => ['nullable', 'string', 'in:'.implode(',', array_keys(FinancialReport::PRESETS))],
            'month' => ['nullable', 'string'],
            'start' => ['nullable', 'string'],
            'end' => ['nullable', 'string'],
        ]);

        return FinancialReport::fromInput($input);
    }
}
