<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RekapPenerimaanHarianController extends Controller
{
    public string $title = "Laporan Harian";
    public string $mainTitle = "Laporan Harian";
    private string $cacheKey = 'Data Rekap Penerimaan Harian';

    private array $allowedFilters = [
        'dari_tanggal' => 'scctcashout.PAIDDT_start',
        'sampai_tanggal' => 'scctcashout.PAIDDT_end',
    ];

    public function __construct()
    {
        $key = Str::slug($this->cacheKey) . '_cache_version';

        Cache::add($key, 1);
    }

    public function index()
    {
        return view('cashless.rekap_harian.index', [
            'title' => $this->title,
            'mainTitle' => $this->mainTitle,
            'columnsUrl' => $this->columnsUrl(),
            'datasUrl' => $this->datasUrl(),
        ]);
    }

    private function columnsUrl(): string
    {
        return route('cashless.rekap-penerimaan-harian.get-column');
    }

    private function datasUrl(): string
    {
        return route('cashless.rekap-penerimaan-harian.get-data');
    }

    public function getColumn()
    {
        return [
            ["data" => null, "name" => "no", "columnType" => "row"],
            [
                "data" => "tanggal",
                "name" => "Tanggal",
                "columnType" => "dateformat",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "total",
                "name" => "Penerimaan",
                "exportable" => true,
                "columnType" => "currency",
                "className" => "text-end"
            ],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get("draw");
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnIndex_arr = $request->get("order", []);
        $columnName_arr = $request->get("columns", []);
        $order_arr = $request->get("order", []);
        $search_arr = $request->get("search", []);
        $searchValue = $search_arr["value"] ?? "";

        $columnName = "tanggal";
        $columnSortOrder = "desc";

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]["column"] ?? null;
            if (
                $columnIndex !== null &&
                in_array($columnName_arr[$columnIndex]["data"] ?? null, ["tanggal", "total"], true)
            ) {
                $columnName = $columnName_arr[$columnIndex]["data"];
                $columnSortOrder = strtolower($order_arr[0]["dir"] ?? "desc") === "asc" ? "asc" : "desc";
            }
        }

        $filters = [];
        $filterQuery = null;

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);

        if ($filter) {
            foreach ($filter as $key => $val) {
                switch ($key) {
                    case 'scctcashout.PAIDDT_start':
                        $date = Carbon::createFromFormat('d-m-Y', $val)->startOfDay();
                        if ($date) {
                            $filters[] = ['scctcashout.TanggalKeluar', '>=', $date];
                        }
                        break;
                    case 'scctcashout.PAIDDT_end':
                        $date = Carbon::createFromFormat('d-m-Y', $val)->endOfDay();
                        if ($date) {
                            $filters[] = ['scctcashout.TanggalKeluar', '<=', $date];
                        }
                        break;
                }
            }

            if (!empty($filters)) {
                $filterQuery = function ($query) use ($filters) {
                    foreach ($filters as $filter) {
                        if (count($filter) === 3) {
                            $query->where($filter[0], $filter[1], $filter[2]);
                        } elseif (count($filter) === 4) {
                            if ($filter[3] == 'whereBetween') {
                                $query->whereBetween($filter[0], [$filter[1], $filter[2]]);
                            } else {
                                $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                            }
                        }
                    }
                };
            }
        }

        $teller = (string) session('user.username');
        $cacheFilter = array_merge($filter, ['teller' => $teller]);

        $query = DB::connection('DATA_MYSQL')->table('scctcashout')
            ->where('scctcashout.teller', '=', $teller)
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            });

        $totalRecords = $this->total();

        $totalRecordswithFilter =
            Cache::remember(
                CacheHandler::cacheKey($this->cacheKey, 'total_filtered_data', $cacheFilter, $searchValue ?? ''),
                now()->addMinutes(10),
                fn() => (int) (clone $query)
                    ->selectRaw('COUNT(DISTINCT DATE(scctcashout.TanggalKeluar)) as aggregate')
                    ->value('aggregate')
            );

        $total =
            Cache::remember(
                CacheHandler::cacheKey($this->cacheKey, 'total_sum_value', $cacheFilter, $searchValue ?? ''),
                now()->addMinutes(10),
                fn() => (clone $query)
                    ->selectRaw('COALESCE(SUM(scctcashout.BILLAM), 0) as total')
                    ->first()
            );

        $rowperpage = $rowperpage == "poll" || (int) $rowperpage < 1 ? max($totalRecordswithFilter, 1) : (int) $rowperpage;
        $records = (clone $query)
            ->selectRaw('DATE(scctcashout.TanggalKeluar) as tanggal')
            ->selectRaw('COALESCE(SUM(scctcashout.BILLAM), 0) as total')
            ->groupByRaw('DATE(scctcashout.TanggalKeluar)')
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $start)
            ->take($rowperpage)
            ->get();

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records,
            'totals' => [
                'total' => ['location' => 2, 'value' => $total->total ?? 0, 'columnType' => 'currency']
            ]
        ];
        return response()->json($response);
    }

    public function total(): int
    {
        $key = Str::slug($this->cacheKey);
        $teller = (string) session('user.username');
        return Cache::remember(
            "{$key}:total_all_data:" . Str::slug($teller),
            now()->addMinutes(10),
            fn() => (int) DB::connection('DATA_MYSQL')->table('scctcashout')
                ->where('teller', '=', $teller)
                ->selectRaw('COUNT(DISTINCT DATE(scctcashout.TanggalKeluar)) as aggregate')
                ->value('aggregate')
        );
    }
}
