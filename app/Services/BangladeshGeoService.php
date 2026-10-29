<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BangladeshGeoService
{
    /** Full division → district → thana → post office (includes Dhaka city thanas). */
    public const SOURCE_URL = 'https://huggingface.co/datasets/ahnafch01/Bangladesh_Locations/resolve/main/bangladesh_postcodes_withBN.csv';

    public const SOURCE_FALLBACK_URL = 'https://huggingface.co/datasets/ahnafch01/Bangladesh_Locations/resolve/main/bangladesh_postcodes_withBN.csv';

    public const POSTAL_URL = 'https://cdn.jsdelivr.net/gh/tlstanjim/bangladesh-postal-codes-database@main/variations/en/postcodes-en.json';

    public const POSTAL_FALLBACK_URL = 'https://raw.githubusercontent.com/tlstanjim/bangladesh-postal-codes-database/main/variations/en/postcodes-en.json';

    /** @deprecated Use SOURCE_URL */
    public const FALLBACK_URL = self::POSTAL_URL;

    /** @return list<string> */
    public function divisions(): array
    {
        return $this->uniqueSorted($this->rows()->pluck('division'));
    }

    /** @return list<string> */
    public function districts(string $division): array
    {
        $division = trim($division);
        if ($division === '') {
            return [];
        }

        return $this->uniqueSorted(
            $this->rows()
                ->filter(fn (array $row) => strcasecmp($row['division'], $division) === 0)
                ->pluck('district')
        );
    }

    /** @return list<string> */
    public function thanas(string $district): array
    {
        $district = trim($district);
        if ($district === '') {
            return [];
        }

        $names = $this->rows()
            ->filter(fn (array $row) => strcasecmp($row['district'], $district) === 0)
            ->pluck('thana')
            ->map(fn ($name) => trim((string) $name))
            ->filter();

        $seen = [];
        $unique = collect();
        foreach ($names as $name) {
            $key = $this->thanaKey($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique->push($name);
        }

        return $unique
            ->sortBy(fn ($name) => mb_strtolower($name), SORT_NATURAL)
            ->values()
            ->all();
    }

    /** @return list<array{name: string, postcode: string}> */
    public function postOffices(string $district, string $thana): array
    {
        $district = trim($district);
        $thana = trim($thana);
        if ($district === '' || $thana === '') {
            return [];
        }

        $thanaKey = $this->thanaKey($thana);

        return $this->rows()
            ->filter(fn (array $row) => strcasecmp($row['district'], $district) === 0 && $this->thanaKey($row['thana']) === $thanaKey)
            ->map(fn (array $row) => [
                'name' => $row['post_office'],
                'postcode' => $row['postcode'],
            ])
            ->filter(fn (array $row) => $row['name'] !== '')
            ->unique(fn (array $row) => mb_strtolower($row['name'].'|'.$row['postcode']))
            ->sortBy(fn (array $row) => mb_strtolower($row['name']), SORT_NATURAL)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function locations(string $thana, ?string $postOffice = null): array
    {
        $names = collect();
        if ($postOffice) {
            $names->push(trim($postOffice));
        }
        $thana = trim($thana);
        if ($thana !== '') {
            $names = $names->merge(
                Shop::query()
                    ->where('thana', $thana)
                    ->when($postOffice, fn ($q) => $q->where('post_office', $postOffice))
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->distinct()
                    ->orderBy('location')
                    ->pluck('location')
            );
        }

        return $this->uniqueSorted($names);
    }

    /** @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}> */
    public function rows(): Collection
    {
        try {
            $cached = $this->geoStore()->get('bd.geo.postcodes.v3');
            if (is_array($cached) && $cached !== []) {
                return collect($cached);
            }
            $rows = $this->loadRows();
            $this->geoStore()->put('bd.geo.postcodes.v3', $rows->all(), now()->addDays(7));

            return $rows;
        } catch (\Throwable $e) {
            report($e);

            return $this->loadRows();
        }
    }

    private function geoStore(): \Illuminate\Contracts\Cache\Repository
    {
        if (config('cache.default') === 'database') {
            return Cache::store('file');
        }

        return Cache::store();
    }

    /** @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}> */
    private function loadRows(): Collection
    {
        $primary = $this->fetchCsv([self::SOURCE_URL, self::SOURCE_FALLBACK_URL]);
        $postal = $this->fetchPostalJson([self::POSTAL_URL, self::POSTAL_FALLBACK_URL]);

        if ($primary->isNotEmpty() && $postal->isNotEmpty()) {
            return $this->mergeRows($primary, $postal);
        }

        return $primary->isNotEmpty() ? $primary : $postal;
    }

    /**
     * @param  list<string>  $urls
     * @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>
     */
    private function fetchCsv(array $urls): Collection
    {
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(25)->withHeaders(['Accept' => 'text/csv,text/plain,*/*'])->get($url);
                if ($response->successful() && is_string($response->body()) && $response->body() !== '') {
                    $rows = $this->normalizeCsv($response->body());
                    if ($rows->isNotEmpty()) {
                        return $rows;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return collect();
    }

    /**
     * @param  list<string>  $urls
     * @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>
     */
    private function fetchPostalJson(array $urls): Collection
    {
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(12)->acceptJson()->get($url);
                if ($response->successful() && is_array($response->json())) {
                    $rows = $this->normalizeJson($response->json());
                    if ($rows->isNotEmpty()) {
                        return $rows;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return collect();
    }

    /**
     * @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>
     */
    private function normalizeCsv(string $csv): Collection
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;
        $lines = preg_split("/\r\n|\n|\r/", $csv) ?: [];
        $header = null;
        $rows = collect();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line);
            if ($header === null) {
                $header = array_map(fn ($col) => strtolower(trim((string) $col)), $cols);
                continue;
            }
            if (count($cols) < 8) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($cols[$i] ?? ''));
            }
            $division = $row['division_name'] ?? $row['division'] ?? '';
            $district = $row['district_name'] ?? $row['district'] ?? '';
            $thana = $row['thana_name'] ?? $row['thana'] ?? $row['upazila'] ?? '';
            $office = $row['postoffice_name'] ?? $row['post_office'] ?? $row['postoffice'] ?? '';
            $postcode = $row['postcode'] ?? $row['post_code'] ?? '';
            if ($division === '' || $district === '' || $thana === '') {
                continue;
            }
            $rows->push([
                'division' => $division,
                'district' => $district,
                'thana' => $thana,
                'post_office' => $office,
                'postcode' => $postcode,
            ]);
        }

        return $rows->values();
    }

    /**
     * @param  array<string|int, mixed>  $payload
     * @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>
     */
    private function normalizeJson(array $payload): Collection
    {
        return collect($payload)
            ->map(function ($row, $code) {
                if (! is_array($row)) {
                    return null;
                }
                $division = trim((string) ($row['division'] ?? data_get($row, 'en.division', '')));
                $district = trim((string) ($row['district'] ?? data_get($row, 'en.district', '')));
                $thana = trim((string) ($row['thana'] ?? data_get($row, 'en.thana', '')));
                $office = trim((string) ($row['suboffice'] ?? $row['post_office'] ?? $row['postOffice'] ?? data_get($row, 'en.suboffice', '')));
                $postcode = trim((string) ($row['postcode'] ?? $row['postCode'] ?? data_get($row, 'en.postcode', $code)));
                if ($division === '' || $district === '' || $thana === '') {
                    return null;
                }

                return [
                    'division' => $division,
                    'district' => $district,
                    'thana' => $thana,
                    'post_office' => $office,
                    'postcode' => $postcode,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>  $primary
     * @param  Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>  $extra
     * @return Collection<int, array{division: string, district: string, thana: string, post_office: string, postcode: string}>
     */
    private function mergeRows(Collection $primary, Collection $extra): Collection
    {
        $canonical = [];
        foreach ($primary as $row) {
            $canonical[$this->placeKey($row['district'], $row['thana'])] = $row['thana'];
        }

        $aligned = $extra->map(function (array $row) use ($canonical) {
            $key = $this->placeKey($row['district'], $row['thana']);
            if (isset($canonical[$key])) {
                $row['thana'] = $canonical[$key];
            }

            return $row;
        });

        return $primary->concat($aligned)->values();
    }

    private function placeKey(string $district, string $thana): string
    {
        return mb_strtolower(trim($district)).'|'.$this->thanaKey($thana);
    }

    private function thanaKey(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = str_replace(['dakkhin', 'daskhin'], 'dakshin', $n);
        $n = preg_replace('/[\s\-_]+/u', '', $n) ?? $n;

        return $n;
    }

    /** @return list<string> */
    private function uniqueSorted(Collection $names): array
    {
        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sortBy(fn ($name) => mb_strtolower($name), SORT_NATURAL)
            ->values()
            ->all();
    }
}
