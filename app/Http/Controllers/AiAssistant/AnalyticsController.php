<?php

namespace App\Http\Controllers\AiAssistant;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiService;
use App\Services\Ai\AnalyticsService;
use App\Services\Ai\GeminiApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        $filters = $this->validatedFilters($request);

        return view('ai-assistant.analytics.index', [
            ...$analytics->filterOptions(),
            'filters' => $filters,
            'metrics' => $analytics->metrics($filters),
        ]);
    }

    public function insight(
        Request $request,
        AnalyticsService $analytics,
        AiService $ai,
    ): JsonResponse {
        $filters = $this->validatedFilters($request);
        $cacheKey = 'ai-assistant:analytics:insight:'.hash('sha256', json_encode([
            'year' => $filters['year'] ?? null,
            'kawasan_id' => $filters['kawasan_id'] ?? null,
            'desa_id' => $filters['desa_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $insight = Cache::remember($cacheKey, now()->addMinutes(30), function () use (
            $filters,
            $analytics,
            $ai,
        ): ?string {
            $metrics = $analytics->metrics($filters);

            if ($metrics['summary']['total'] === 0) {
                return null;
            }

            $aggregateData = [
                'jumlah_kelompok' => $metrics['summary']['total'],
                'status_kelompok' => [
                    'aktif' => $metrics['summary']['active'],
                    'tidak_aktif' => $metrics['summary']['inactive'],
                ],
                'anggota_terdata' => [
                    'laki_laki' => $metrics['summary']['male_members'],
                    'perempuan' => $metrics['summary']['female_members'],
                ],
                'total_bantuan' => $metrics['summary']['total_aid'],
                'sebaran_per_kawasan' => array_map(
                    fn (array $area): array => [
                        'kawasan' => $area['label'],
                        'jumlah_kelompok' => $area['value'],
                    ],
                    $metrics['charts']['kawasan'],
                ),
            ];

            try {
                return $ai->generateText(
                    'Anda adalah analis data pemberdayaan BKSDA. Berikan insight dan rekomendasi ringkas dalam bahasa '
                    .'Indonesia hanya berdasarkan ringkasan statistik agregat yang disertakan. Jangan mengarang '
                    .'angka, persentase, target, atau peringkat; sebutkan angka hanya jika nilainya ada persis pada '
                    .'data. Gunakan sebaran per kawasan untuk memberi rekomendasi praktis. Jangan meminta atau '
                    .'menyimpulkan identitas kelompok, ketua, atau anggota. Data yang diberikan tidak mencakup '
                    .'kontak atau identitas pribadi.',
                    [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => "Ringkasan agregat sesuai filter aktif (JSON):\n"
                                .json_encode($aggregateData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]],
                    ]],
                    allowFallback: true,
                );
            } catch (GeminiApiException $exception) {
                Log::warning('AI analytics providers failed; using local aggregate insight.', [
                    'http_status' => $exception->providerHttpStatus ?? $exception->statusCode,
                ]);

                return $this->localInsight($aggregateData);
            }
        });

        if ($insight === null) {
            return response()->json([
                'insight' => 'Tidak ada data kelompok yang sesuai dengan filter. Ubah filter atau tambahkan data sebelum membuat insight.',
            ]);
        }

        return response()->json(['insight' => $insight]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'kawasan_id' => ['nullable', 'integer', 'exists:kawasans,id'],
            'desa_id' => ['nullable', 'integer', 'exists:desas,id'],
            'status' => ['nullable', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);
    }

    /**
     * @param  array{
     *     jumlah_kelompok: int,
     *     status_kelompok: array{aktif: int, tidak_aktif: int},
     *     anggota_terdata: array{laki_laki: int, perempuan: int},
     *     total_bantuan: float,
     *     sebaran_per_kawasan: list<array{kawasan: string, jumlah_kelompok: int}>
     * }  $aggregateData
     */
    private function localInsight(array $aggregateData): string
    {
        $areas = array_map(
            fn (array $area): string => $area['kawasan'].' ('.number_format($area['jumlah_kelompok']).' kelompok)',
            array_slice($aggregateData['sebaran_per_kawasan'], 0, 5),
        );
        $remainingAreaCount = count($aggregateData['sebaran_per_kawasan']) - count($areas);

        if ($remainingAreaCount > 0) {
            $areas[] = $remainingAreaCount.' kawasan lainnya';
        }

        $areaSummary = $areas === [] ? 'Sebaran kawasan belum tersedia' : implode('; ', $areas);
        $inactiveGroups = $aggregateData['status_kelompok']['tidak_aktif'];
        $recommendation = $inactiveGroups > 0
            ? 'Prioritaskan pendampingan lanjutan pada kelompok berstatus tidak aktif.'
            : 'Pertahankan pendampingan dan pemantauan rutin.';

        return 'Insight: Data mencatat '.number_format($aggregateData['jumlah_kelompok']).' kelompok, terdiri dari '
            .number_format($aggregateData['status_kelompok']['aktif']).' aktif dan '
            .number_format($inactiveGroups).' tidak aktif. Anggota terdata: '
            .number_format($aggregateData['anggota_terdata']['laki_laki']).' laki-laki dan '
            .number_format($aggregateData['anggota_terdata']['perempuan']).' perempuan. Total bantuan tercatat Rp'
            .number_format($aggregateData['total_bantuan'], 0, ',', '.').'. Sebaran per kawasan: '.$areaSummary
            .'. Rekomendasi: '.$recommendation;
    }
}
