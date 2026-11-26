<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Models\AiGroupEvaluation;
use App\Models\Kelompok;
use App\Services\Ai\GeminiApiException;
use App\Services\Ai\MonitoringEvaluationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(): View
    {
        $groups = Kelompok::query()
            ->with(['kawasan:id,nama_kawasan', 'desa:id,nama_desa'])
            ->orderBy('nama_kelompok')
            ->paginate(20)
            ->withQueryString();

        return view('ai-assistant.monev.index', [
            'groups' => $groups,
            'unmonitoredCount' => Kelompok::query()->whereNull('tahun_monitoring')->count(),
            'withoutMonevNotesCount' => Kelompok::query()
                ->whereNull('catatan_kebutuhan_hasil_monev')
                ->orWhereRaw("TRIM(catatan_kebutuhan_hasil_monev) = ''")
                ->count(),
        ]);
    }

    public function show(Kelompok $kelompok, MonitoringEvaluationService $evaluations): View
    {
        $kelompok->load(['kawasan:id,nama_kawasan', 'desa:id,nama_desa', 'aiEvaluation']);
        $evaluation = $kelompok->aiEvaluation;

        return view('ai-assistant.monev.show', [
            'kelompok' => $kelompok,
            'evaluation' => $evaluation,
            'hasMonevData' => $evaluations->hasMonevData($kelompok),
            'evaluationIsStale' => $evaluation instanceof AiGroupEvaluation
                && ! hash_equals($evaluation->source_hash, $evaluations->sourceHash($kelompok)),
        ]);
    }

    public function analyze(
        Request $request,
        Kelompok $kelompok,
        MonitoringEvaluationService $evaluations,
    ): RedirectResponse {
        if (! $evaluations->hasMonevData($kelompok)) {
            return back()->with('monev_notice', 'Data monitoring dan catatan Monev kelompok ini belum tersedia. AI tidak dijalankan agar tidak mengarang hasil.');
        }

        try {
            $evaluations->evaluate($kelompok, (int) $request->user()->getAuthIdentifier());
        } catch (GeminiApiException $exception) {
            return back()->withErrors(['gemini' => $exception->getMessage()]);
        }

        return redirect()
            ->route('ai.monev.show', $kelompok)
            ->with('status', 'Analisis Monev berhasil diperbarui.');
    }
}
