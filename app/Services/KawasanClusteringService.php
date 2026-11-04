<?php

namespace App\Services;

class KawasanClusteringService
{
    private const CLUSTER_COUNT = 3;

    private const MAX_ITERATIONS = 100;

    public function cluster(array $rows): array
    {
        $samples = [];
        $excludedCount = 0;

        foreach ($rows as $row) {
            $totalNte = $row['total_nte'] ?? null;
            $jumlahDesa = $row['jumlah_desa_sekitar'] ?? null;
            $desaDifasilitasi = $row['jumlah_desa_difasilitasi'] ?? null;

            if (
                ! is_numeric($totalNte)
                || ! is_numeric($jumlahDesa)
                || ! is_numeric($desaDifasilitasi)
                || (float) $totalNte < 0
                || (float) $jumlahDesa <= 0
                || (float) $desaDifasilitasi < 0
                || (float) $desaDifasilitasi > (float) $jumlahDesa
            ) {
                $excludedCount++;

                continue;
            }

            $samples[] = [
                'label' => (string) ($row['nama_kawasan'] ?? 'Kawasan tanpa nama'),
                'features' => [
                    (float) $totalNte,
                    (float) $jumlahDesa,
                    (float) $desaDifasilitasi / (float) $jumlahDesa,
                ],
            ];
        }

        $base = [
            'method' => 'K-Means',
            'k' => self::CLUSTER_COUNT,
            'features' => [
                'Total NTE',
                'Jumlah desa sekitar',
                'Rasio desa difasilitasi',
            ],
            'sample_count' => count($samples),
            'excluded_count' => $excludedCount,
            'silhouette' => null,
            'clusters' => [],
            'points' => [],
        ];

        if (count($samples) < self::CLUSTER_COUNT) {
            return $base + ['status' => 'insufficient_samples'];
        }

        $normalized = $this->normalize(array_column($samples, 'features'));

        if (count(array_unique(array_map('serialize', $normalized))) < self::CLUSTER_COUNT) {
            return $base + ['status' => 'insufficient_variation'];
        }

        $centroids = $this->initializeCentroids($normalized);
        $assignments = [];

        for ($iteration = 0; $iteration < self::MAX_ITERATIONS; $iteration++) {
            $nextAssignments = $this->assign($normalized, $centroids);
            $this->fillEmptyClusters($normalized, $nextAssignments, $centroids);
            $nextCentroids = $this->calculateCentroids($normalized, $nextAssignments);
            $converged = $nextAssignments === $assignments;

            $assignments = $nextAssignments;
            $centroids = $nextCentroids;

            if ($converged) {
                break;
            }
        }

        $clusterMeans = [];

        foreach ($assignments as $index => $cluster) {
            $clusterMeans[$cluster][] = $samples[$index]['features'][0];
        }

        $orderedClusters = array_keys($clusterMeans);
        usort($orderedClusters, function (int $left, int $right) use ($clusterMeans): int {
            return (array_sum($clusterMeans[$left]) / count($clusterMeans[$left]))
                <=> (array_sum($clusterMeans[$right]) / count($clusterMeans[$right]));
        });

        $clusterNames = [];
        foreach ($orderedClusters as $rank => $cluster) {
            $clusterNames[$cluster] = ['NTE Rendah', 'NTE Menengah', 'NTE Tinggi'][$rank];
        }

        $groups = [];
        foreach ($orderedClusters as $cluster) {
            $members = [];

            foreach ($assignments as $index => $assignedCluster) {
                if ($assignedCluster === $cluster) {
                    $members[] = $samples[$index]['features'];
                }
            }

            $count = count($members);
            $groups[] = [
                'name' => $clusterNames[$cluster],
                'count' => $count,
                'mean_nte_million' => round(array_sum(array_column($members, 0)) / $count / 1_000_000, 1),
                'mean_villages' => round(array_sum(array_column($members, 1)) / $count, 1),
                'mean_coverage_percent' => round(array_sum(array_column($members, 2)) / $count * 100, 1),
            ];
        }

        $points = [];
        foreach ($samples as $index => $sample) {
            $points[] = [
                'label' => $sample['label'],
                'x' => round($sample['features'][0] / 1_000_000, 1),
                'y' => $sample['features'][1],
                'cluster' => $clusterNames[$assignments[$index]],
            ];
        }

        return array_merge($base, [
            'status' => 'ok',
            'silhouette' => round($this->silhouetteScore($normalized, $assignments), 3),
            'clusters' => $groups,
            'points' => $points,
        ]);
    }

    private function normalize(array $rows): array
    {
        $minimums = [];
        $maximums = [];

        foreach ($rows as $row) {
            foreach ($row as $feature => $value) {
                $minimums[$feature] = min($minimums[$feature] ?? $value, $value);
                $maximums[$feature] = max($maximums[$feature] ?? $value, $value);
            }
        }

        return array_map(function (array $row) use ($minimums, $maximums): array {
            return array_map(function (float $value, int $feature) use ($minimums, $maximums): float {
                $range = $maximums[$feature] - $minimums[$feature];

                return $range === 0.0 ? 0.0 : ($value - $minimums[$feature]) / $range;
            }, $row, array_keys($row));
        }, $rows);
    }

    private function initializeCentroids(array $points): array
    {
        $firstIndex = 0;
        $lowestTotal = INF;

        foreach ($points as $index => $point) {
            $total = array_sum($point);
            if ($total < $lowestTotal) {
                $lowestTotal = $total;
                $firstIndex = $index;
            }
        }

        $centroids = [$points[$firstIndex]];

        while (count($centroids) < self::CLUSTER_COUNT) {
            $farthestIndex = 0;
            $farthestDistance = -1.0;

            foreach ($points as $index => $point) {
                $nearestDistance = min(array_map(
                    fn (array $centroid): float => $this->squaredDistance($point, $centroid),
                    $centroids,
                ));

                if ($nearestDistance > $farthestDistance) {
                    $farthestDistance = $nearestDistance;
                    $farthestIndex = $index;
                }
            }

            $centroids[] = $points[$farthestIndex];
        }

        return $centroids;
    }

    private function assign(array $points, array $centroids): array
    {
        return array_map(function (array $point) use ($centroids): int {
            $nearestCluster = 0;
            $nearestDistance = INF;

            foreach ($centroids as $cluster => $centroid) {
                $distance = $this->squaredDistance($point, $centroid);
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestCluster = $cluster;
                }
            }

            return $nearestCluster;
        }, $points);
    }

    private function fillEmptyClusters(array $points, array &$assignments, array $centroids): void
    {
        $counts = array_count_values($assignments);

        foreach (range(0, self::CLUSTER_COUNT - 1) as $emptyCluster) {
            if (($counts[$emptyCluster] ?? 0) > 0) {
                continue;
            }

            $candidateIndex = null;
            $largestDistance = -1.0;

            foreach ($assignments as $index => $cluster) {
                if (($counts[$cluster] ?? 0) <= 1) {
                    continue;
                }

                $distance = $this->squaredDistance($points[$index], $centroids[$cluster]);
                if ($distance > $largestDistance) {
                    $largestDistance = $distance;
                    $candidateIndex = $index;
                }
            }

            if ($candidateIndex !== null) {
                $counts[$assignments[$candidateIndex]]--;
                $assignments[$candidateIndex] = $emptyCluster;
                $counts[$emptyCluster] = 1;
            }
        }
    }

    private function calculateCentroids(array $points, array $assignments): array
    {
        $sums = array_fill(0, self::CLUSTER_COUNT, [0.0, 0.0, 0.0]);
        $counts = array_fill(0, self::CLUSTER_COUNT, 0);

        foreach ($assignments as $index => $cluster) {
            foreach ($points[$index] as $feature => $value) {
                $sums[$cluster][$feature] += $value;
            }
            $counts[$cluster]++;
        }

        foreach ($sums as $cluster => $sum) {
            if ($counts[$cluster] > 0) {
                $sums[$cluster] = array_map(fn (float $value): float => $value / $counts[$cluster], $sum);
            }
        }

        return $sums;
    }

    private function silhouetteScore(array $points, array $assignments): float
    {
        $clusters = array_unique($assignments);
        if (count($clusters) < 2) {
            return 0.0;
        }

        $scores = [];

        foreach ($points as $index => $point) {
            $ownCluster = $assignments[$index];
            $withinDistances = [];
            $otherDistances = [];

            foreach ($points as $otherIndex => $otherPoint) {
                if ($index === $otherIndex) {
                    continue;
                }

                $distance = sqrt($this->squaredDistance($point, $otherPoint));
                if ($assignments[$otherIndex] === $ownCluster) {
                    $withinDistances[] = $distance;
                } else {
                    $otherDistances[$assignments[$otherIndex]][] = $distance;
                }
            }

            if ($withinDistances === []) {
                $scores[] = 0.0;

                continue;
            }

            $withinMean = array_sum($withinDistances) / count($withinDistances);
            $otherMeans = array_map(
                fn (array $distances): float => array_sum($distances) / count($distances),
                $otherDistances,
            );
            $nearestOtherMean = min($otherMeans);
            $denominator = max($withinMean, $nearestOtherMean);
            $scores[] = $denominator === 0.0 ? 0.0 : ($nearestOtherMean - $withinMean) / $denominator;
        }

        return array_sum($scores) / count($scores);
    }

    private function squaredDistance(array $left, array $right): float
    {
        $distance = 0.0;

        foreach ($left as $feature => $value) {
            $difference = $value - $right[$feature];
            $distance += $difference * $difference;
        }

        return $distance;
    }
}
