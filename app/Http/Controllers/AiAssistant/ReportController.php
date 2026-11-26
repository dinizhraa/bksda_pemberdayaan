<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kawasan;
use App\Models\Kelompok;
use App\Services\Ai\AnalyticsService;
use App\Services\Ai\GeminiApiException;
use App\Services\Ai\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(AnalyticsService $analytics): View
    {
        return view('ai-assistant.reports.index', [
            ...$analytics->filterOptions(),
            'report' => null,
            'reportId' => null,
            'filterLabels' => [],
        ]);
    }

    public function preview(
        Request $request,
        AnalyticsService $analytics,
        ReportService $reports,
    ): View|RedirectResponse {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys($reports->types()))],
            'period_start' => ['nullable', 'integer', 'between:1900,2100'],
            'period_end' => ['nullable', 'integer', 'between:1900,2100'],
            'kawasan_id' => ['nullable', 'integer', 'exists:kawasans,id'],
            'desa_id' => ['nullable', 'integer', 'exists:desas,id'],
            'kelompok_id' => ['nullable', 'integer', 'exists:kelompoks,id'],
        ]);

        if (isset($validated['period_start'], $validated['period_end'])
            && $validated['period_start'] > $validated['period_end']) {
            return back()
                ->withErrors(['period_end' => 'Akhir periode harus sama atau lebih besar dari awal periode.'])
                ->withInput();
        }

        $filters = array_filter([
            'from_year' => $validated['period_start'] ?? null,
            'to_year' => $validated['period_end'] ?? null,
            'kawasan_id' => $validated['kawasan_id'] ?? null,
            'desa_id' => $validated['desa_id'] ?? null,
            'kelompok_id' => $validated['kelompok_id'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== '');

        try {
            $report = $reports->generate($filters, $validated['type']);
        } catch (GeminiApiException $exception) {
            return back()->withErrors(['gemini' => $exception->getMessage()])->withInput();
        }

        $report['filter_labels'] = $this->filterLabels($filters);
        $reportId = (string) Str::uuid();
        $savedReports = $request->session()->get('ai_reports', []);
        $savedReports[$reportId] = $report;
        $request->session()->put('ai_reports', array_slice($savedReports, -5, null, true));

        return view('ai-assistant.reports.index', [
            ...$analytics->filterOptions(),
            'filters' => $validated,
            'report' => $report,
            'reportId' => $reportId,
            'filterLabels' => $report['filter_labels'],
        ]);
    }

    public function word(Request $request, string $reportId): StreamedResponse
    {
        $report = $request->session()->get("ai_reports.$reportId");
        abort_unless(is_array($report), 404);

        return response()->streamDownload(
            function () use ($report): void {
                echo view('ai-assistant.reports.word', compact('report'))->render();
            },
            Str::slug($report['title']).'.doc',
            ['Content-Type' => 'application/msword; charset=UTF-8'],
        );
    }

    /**
     * @param  array<string, int|string>  $filters
     * @return array<string, string>
     */
    private function filterLabels(array $filters): array
    {
        $labels = [];

        if (isset($filters['from_year']) || isset($filters['to_year'])) {
            $labels['Periode'] = ($filters['from_year'] ?? 'awal data')
                .'–'.($filters['to_year'] ?? 'terbaru');
        }

        if (isset($filters['kawasan_id'])) {
            $labels['Kawasan'] = Kawasan::query()->whereKey($filters['kawasan_id'])->value('nama_kawasan') ?? '-';
        }

        if (isset($filters['desa_id'])) {
            $labels['Desa'] = Desa::query()->whereKey($filters['desa_id'])->value('nama_desa') ?? '-';
        }

        if (isset($filters['kelompok_id'])) {
            $labels['Kelompok'] = Kelompok::query()->whereKey($filters['kelompok_id'])->value('nama_kelompok') ?? '-';
        }

        return $labels;
    }
}
