<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CyberKeyAuthService
{
    private static ?string $passwordColumn = null;

    /**
     * @return array{ok: bool, message?: string, user?: array<string, mixed>}
     */
    public function login(string $login, string $password): array
    {
        $login = trim($login);
        if ($login === '' || $password === '') {
            return ['ok' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        try {
            $pdo = DB::connection(config('database.default', 'mysql'));
            $table = (string) config('sso.cyber_key_table', 'cyber_key');
            $passwordCol = $this->resolvePasswordColumn($pdo, $table);

            $fidSelect = $this->hasColumn($pdo, $table, 'fid')
                ? 'TRIM(CAST(fid AS CHAR)) AS fid'
                : "'' AS fid";
            $kelSelect = $this->hasColumn($pdo, $table, 'kel')
                ? 'TRIM(kel) AS kel'
                : "'' AS kel";
            $kunciSelect = $this->hasColumn($pdo, $table, 'kunci')
                ? 'TRIM(kunci) AS kunci'
                : "'' AS kunci";

            $where = 'LOWER(TRIM(users)) = LOWER(TRIM(?))';
            $bindings = [$login];

            if ($this->hasColumn($pdo, $table, 'deleted_at')) {
                $where .= ' AND deleted_at IS NULL';
            }

            $kunciFilter = trim((string) config('sso.kunci', ''));
            if ($kunciFilter !== '' && $this->hasColumn($pdo, $table, 'kunci')) {
                $where .= ' AND TRIM(kunci) = ?';
                $bindings[] = $kunciFilter;
            }

            $sql = "
                SELECT
                    urut AS id,
                    users AS username,
                    ket AS name,
                    {$passwordCol} AS password_hash,
                    {$fidSelect},
                    {$kelSelect},
                    {$kunciSelect}
                FROM `{$table}`
                WHERE {$where}
                LIMIT 1
            ";

            $row = $pdo->selectOne($sql, $bindings);
            if (!$row) {
                return ['ok' => false, 'message' => 'Username atau password salah.'];
            }

            $row = (array) $row;
            $hash = trim((string) ($row['password_hash'] ?? ''));
            if ($hash === '' || !$this->verifyPassword($password, $hash)) {
                return ['ok' => false, 'message' => 'Username atau password salah.'];
            }

            $uid = (int) ($row['id'] ?? 0);
            if ($uid > 0 && $this->hasColumn($pdo, $table, 'last_login')) {
                $pdo->update("UPDATE {$table} SET last_login = NOW() WHERE urut = ?", [$uid]);
            }

            $fid = trim((string) ($row['fid'] ?? ''));
            $isSuperadmin = $fid === '';
            $sekolahNama = '';

            if (!$isSuperadmin && $fid !== '' && $this->tableExists($pdo, 'mst_sekolah')) {
                $sk = $pdo->selectOne(
                    'SELECT TRIM(CODE01) AS code01, TRIM(DESC01) AS desc01 FROM mst_sekolah WHERE TRIM(CODE01) = ? LIMIT 1',
                    [$fid]
                );
                if ($sk) {
                    $sekolahNama = trim((string) (((array) $sk)['desc01'] ?? ''));
                }
            }

            return [
                'ok' => true,
                'user' => [
                    'id' => $uid,
                    'username' => trim((string) ($row['username'] ?? '')),
                    'name' => trim((string) ($row['name'] ?? '')),
                    'email' => '',
                    'unit' => $sekolahNama,
                    'fid' => $fid,
                    'kel' => trim((string) ($row['kel'] ?? '')),
                    'kunci' => trim((string) ($row['kunci'] ?? '')),
                    'is_superadmin' => $isSuperadmin,
                    'sekolah_code01' => $isSuperadmin ? '' : $fid,
                    'sekolah_nama' => $sekolahNama,
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[CyberKey SSO] login failed', [
                'message' => $e->getMessage(),
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
            ]);

            $message = 'Tidak dapat terhubung ke server autentikasi.';
            if (config('app.debug')) {
                $message .= ' (' . $e->getMessage() . ')';
            }

            return ['ok' => false, 'message' => $message];
        }
    }

    private function verifyPassword(string $plain, string $hash): bool
    {
        if (hash_equals($hash, md5($plain))) {
            return true;
        }

        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return hash_equals(strtolower($hash), md5($plain));
        }

        return false;
    }

    private function resolvePasswordColumn($pdo, string $table): string
    {
        if (self::$passwordColumn !== null) {
            return self::$passwordColumn;
        }

        if ($this->hasColumn($pdo, $table, 'password')) {
            return self::$passwordColumn = 'password';
        }

        if ($this->hasColumn($pdo, $table, 'pw')) {
            return self::$passwordColumn = 'pw';
        }

        return self::$passwordColumn = 'password';
    }

    private function hasColumn($pdo, string $table, string $column): bool
    {
        try {
            $rows = $pdo->select('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?', [$column]);

            return count($rows) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists($pdo, string $table): bool
    {
        try {
            $pdo->selectOne('SELECT 1 FROM `' . str_replace('`', '', $table) . '` LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
