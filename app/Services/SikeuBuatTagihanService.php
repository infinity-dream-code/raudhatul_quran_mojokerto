<?php

namespace App\Services;

use App\Support\BillacPeriode;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Query Buat Tagihan langsung ke DB SIKEU (fallback bila WS getBuatTagihan gagal/timeout).
 */
class SikeuBuatTagihanService
{
    public function isConfigured(): bool
    {
        return trim((string) config('database.connections.sikeu.database', '')) !== '';
    }

    protected function conn(): Connection
    {
        return DB::connection('sikeu');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function query(array $filters, int $limit = 10, int $offset = 0): array
    {
        $thnAkademik = trim((string) ($filters['thn_akademik'] ?? ''));
        $thnAngkatan = trim((string) ($filters['thn_angkatan'] ?? ''));
        $kelasId = trim((string) ($filters['kelas_id'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $tagihan = trim((string) ($filters['tagihan'] ?? ''));
        $fungsi = BillacPeriode::resolveByTagihan(
            $tagihan,
            trim((string) ($filters['fungsi'] ?? '')),
            $thnAkademik
        );

        $thnAngkatanBase = trim((string) preg_replace('/\s*-\s*.*/', '', $thnAngkatan));
        $limit = min(max($limit, 1), 200);
        $offset = max($offset, 0);

        $conn = $this->conn();
        $kelasRow = [];
        if ($kelasId !== '') {
            $kelasRow = (array) ($conn->table('mst_kelas')
                ->where('id', (int) $kelasId)
                ->first(['id', 'kelas', 'jenjang', 'unit', 'kelompok']) ?? []);
        }

        $kelasNama = trim((string) ($kelasRow['kelas'] ?? ''));
        $jenjangNama = trim((string) ($kelasRow['jenjang'] ?? ''));
        $unitNama = trim((string) ($kelasRow['unit'] ?? ''));

        $base = $conn->table('scctcust as c');
        if ($thnAngkatan !== '') {
            $base->where(function ($q) use ($thnAngkatan, $thnAngkatanBase) {
                $q->whereRaw('TRIM(c.DESC04) = ?', [$thnAngkatan])
                    ->orWhereRaw('TRIM(c.DESC04) = ?', [$thnAngkatanBase]);
            });
        }

        if ($kelasId !== '') {
            if ($kelasRow === []) {
                $base->whereRaw('1 = 0');
            } else {
                $base->where(function ($q) use ($kelasId, $kelasNama, $jenjangNama, $unitNama) {
                    $q->whereRaw("(
                        TRIM(c.CODE03) REGEXP '^[0-9]+$'
                        AND CAST(TRIM(c.CODE03) AS UNSIGNED) = ?
                    )", [(int) $kelasId])
                        ->orWhereRaw('TRIM(c.CODE03) = ?', [$kelasId])
                        ->orWhere(function ($q2) use ($jenjangNama, $kelasNama, $unitNama) {
                            $q2->whereRaw('TRIM(c.DESC02) = ?', [$jenjangNama])
                                ->whereRaw('TRIM(c.DESC03) = ?', [$kelasNama])
                                ->whereRaw('TRIM(c.CODE02) = ?', [$unitNama]);
                        });
                });
            }
        }

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->whereRaw('TRIM(c.NMCUST) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('TRIM(c.NOCUST) LIKE ?', ['%' . $search . '%']);
            });
        }

        $totalSiswa = (clone $base)->count();

        $siswa = (clone $base)
            ->leftJoin('mst_kelas as mk', DB::raw('CAST(mk.id AS CHAR)'), '=', DB::raw('TRIM(c.CODE03)'))
            ->orderBy('c.NMCUST')
            ->offset($offset)
            ->limit($limit)
            ->get([
                'c.CUSTID',
                DB::raw('TRIM(c.NOCUST) AS NIS'),
                DB::raw('TRIM(c.NMCUST) AS NAMA'),
                DB::raw('TRIM(c.CODE01) AS CODE01'),
                DB::raw('TRIM(c.CODE03) AS kelas_id'),
                DB::raw("COALESCE(NULLIF(TRIM(mk.jenjang), ''), TRIM(c.DESC02)) AS KELAS"),
                DB::raw("COALESCE(NULLIF(TRIM(mk.kelas), ''), TRIM(c.DESC03)) AS JENJANG"),
                DB::raw('TRIM(c.DESC04) AS ANGKATAN'),
                DB::raw('TRIM(c.CODE02) AS unit'),
            ])
            ->map(static fn ($row) => (array) $row)
            ->all();

        $daftarHarga = [];
        if ($kelasId !== '') {
            $resolvedKodeProd = $this->resolveKodeProdForDaftarHarga($conn, $kelasId, $thnAngkatan, $thnAngkatanBase);
            $daftarHarga = $this->fetchDaftarHarga($conn, $resolvedKodeProd, $thnAngkatan, $thnAngkatanBase);

            if ($daftarHarga === [] && $siswa !== []) {
                $rawCode01 = trim((string) ($siswa[0]['CODE01'] ?? ''));
                $code01Num = ltrim(preg_replace('/\D+/', '', $rawCode01), '0');
                if ($code01Num !== '') {
                    $daftarHarga = $this->fetchDaftarHarga($conn, $code01Num, $thnAngkatan, $thnAngkatanBase);
                }
            }
        }

        return [
            'kelas' => $kelasRow,
            'thn_akademik' => $thnAkademik,
            'thn_angkatan' => $thnAngkatan,
            'tagihan' => $tagihan,
            'fungsi' => $fungsi,
            'total_siswa' => $totalSiswa,
            'siswa' => $siswa,
            'daftar_harga' => $daftarHarga,
        ];
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    protected function fetchDaftarHarga(Connection $conn, string $kodeProd, string $thnAngkatan, string $thnAngkatanBase): array
    {
        $kodeProd = trim($kodeProd);
        if ($kodeProd === '') {
            return [];
        }

        $query = $conn->table('u_daftar_harga as d')
            ->whereRaw('TRIM(d.kode_prod) = ?', [$kodeProd]);

        if ($thnAngkatan !== '' || $thnAngkatanBase !== '') {
            $baseAngkatan = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
            $query->where(function ($q) use ($thnAngkatan, $baseAngkatan) {
                $q->whereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$thnAngkatan])
                    ->orWhereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$baseAngkatan])
                    ->orWhereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(?), ' ', ''), '%')", [$baseAngkatan]);
            });
        }

        return $query
            ->orderBy('d.urut')
            ->get([
                'd.urut',
                DB::raw('TRIM(d.KodeAkun) AS KodeAkun'),
                DB::raw("COALESCE(
                    NULLIF(TRIM(d.NamaAkun), ''),
                    (SELECT TRIM(a.NamaAkun) FROM u_akun a WHERE TRIM(a.KodeAkun) = TRIM(d.KodeAkun) LIMIT 1)
                ) AS NamaAkun"),
                DB::raw('TRIM(d.nominal) AS nominal'),
                DB::raw('TRIM(d.NoRek) AS NoRek'),
            ])
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    protected function countDaftarHargaForKodeProd(Connection $conn, string $kodeProd, string $thnAngkatan, string $thnAngkatanBase): int
    {
        $kodeProd = trim($kodeProd);
        if ($kodeProd === '') {
            return 0;
        }

        $query = $conn->table('u_daftar_harga as d')
            ->whereRaw('TRIM(d.kode_prod) = ?', [$kodeProd]);

        if ($thnAngkatan !== '' || $thnAngkatanBase !== '') {
            $baseAngkatan = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
            $query->where(function ($q) use ($thnAngkatan, $baseAngkatan) {
                $q->whereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$thnAngkatan])
                    ->orWhereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$baseAngkatan])
                    ->orWhereRaw("REPLACE(TRIM(d.thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(?), ' ', ''), '%')", [$baseAngkatan]);
            });
        }

        return (int) $query->count();
    }

    protected function resolveKodeProdForDaftarHarga(Connection $conn, string $kelasId, string $thnAngkatan, string $thnAngkatanBase): string
    {
        $kelasId = trim($kelasId);
        if ($kelasId === '') {
            return '';
        }

        $kelompok = trim((string) ($conn->table('mst_kelas')
            ->where('id', (int) $kelasId)
            ->value('kelompok') ?? ''));

        $candidates = array_values(array_unique(array_filter([
            $kelompok,
            $kelasId,
        ], static fn ($v) => trim((string) $v) !== '')));

        foreach ($candidates as $candidate) {
            if ($this->countDaftarHargaForKodeProd($conn, (string) $candidate, $thnAngkatan, $thnAngkatanBase) > 0) {
                return (string) $candidate;
            }
        }

        $kelasPad2 = str_pad((string) ((int) $kelasId), 2, '0', STR_PAD_LEFT);
        $row = $conn->table('u_daftar_harga')
            ->whereRaw('TRIM(kode_fak) = ?', [$kelasPad2])
            ->when($thnAngkatan !== '' || $thnAngkatanBase !== '', function ($q) use ($thnAngkatan, $thnAngkatanBase) {
                $baseAngkatan = $thnAngkatanBase !== '' ? $thnAngkatanBase : $thnAngkatan;
                $q->where(function ($q2) use ($thnAngkatan, $baseAngkatan) {
                    $q2->whereRaw("REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$thnAngkatan])
                        ->orWhereRaw("REPLACE(TRIM(thn_masuk), ' ', '') = REPLACE(TRIM(?), ' ', '')", [$baseAngkatan])
                        ->orWhereRaw("REPLACE(TRIM(thn_masuk), ' ', '') LIKE CONCAT(REPLACE(TRIM(?), ' ', ''), '%')", [$baseAngkatan]);
                });
            })
            ->orderBy('urut')
            ->value(DB::raw('TRIM(kode_prod)'));

        $resolved = trim((string) $row);

        return $resolved !== '' ? $resolved : $kelasId;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, message: string, data: array<string, mixed>}
     */
    public function safeQuery(array $filters, int $limit = 10, int $offset = 0): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Database SIKEU belum dikonfigurasi.', 'data' => []];
        }

        try {
            return [
                'ok' => true,
                'message' => '',
                'data' => $this->query($filters, $limit, $offset),
            ];
        } catch (Throwable $e) {
            Log::error('[SikeuBuatTagihan] ' . $e->getMessage(), ['filters' => $filters]);

            return ['ok' => false, 'message' => 'Gagal memuat data dari database: ' . $e->getMessage(), 'data' => []];
        }
    }
}
