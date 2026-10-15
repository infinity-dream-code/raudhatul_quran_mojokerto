<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiwayatPencairanController extends Controller
{
    private string $title = "Data Riwayat Pencairan";
    private string $mainTitle = 'Data Riwayat Pencairan';
    private string $cacheKey = 'Data Riwayat Pencairan';
    private array $allowedFilters = [
        'dari_tanggal' => 'sm_mercan_cair.dari_tgl_tran',
        'sampai_tanggal' => 'sm_mercan_cair.akhir_tgl_tran',
        'tanggal' => 'sm_mercan_cair.TglTerima',
    ];

    public function __construct()
    {
        $key = Str::slug($this->cacheKey) . '_cache_version';

        Cache::add($key, 1);
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['columnsUrl'] = $this->columnsUrl();
        $data['datasUrl'] = $this->dataUrl();

        return view('cashless.riwayat_pencairan.index', $data);
    }

    private function columnsUrl(): string
    {
        return route('cashless.riwayat-pencairan.get-column');
    }

    private function dataUrl(): string
    {
        return route('cashless.riwayat-pencairan.get-data');
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'columnType' => 'row', 'exportable' => true],
            ['data' => 'Nominal', 'name' => 'Nominal', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'TglTerima', 'name' => 'Tanggal Pencairan', 'searchable' => true, 'orderable' => true, 'exportable' => true, "columnType" => "timestamp"],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");
        $columnIndex_arr = $request->get('order', []);
        $columnName_arr = $request->get('columns', []);
        $order_arr = $request->get('order', []);
        $search_arr = $request->get('search', []);
        $searchValue = $search_arr['value'] ?? '';

        $columnName = "sm_mercan_cair.TglTerima";
        $columnSortOrder = "desc";

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]["column"] ?? null;
            if (
                $columnIndex !== null &&
                in_array($columnName_arr[$columnIndex]["data"] ?? null, ['Nominal', 'TglTerima'], true)
            ) {
                $columnName = 'sm_mercan_cair.' . $columnName_arr[$columnIndex]["data"];
                $columnSortOrder = strtolower($order_arr[0]["dir"] ?? "desc") === "asc" ? "asc" : "desc";
            }
        }

        $filters = [];
        $filterQuery = null;

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);

        if ($filter) {
            foreach ($filter as $key => $val) {
                switch ($key) {
                    case 'sm_mercan_cair.dari_tgl_tran':
                        $date = Carbon::createFromFormat('d-m-Y', $val);
                        if ($date) {
                            $filters[] = ['sm_mercan_cair.dari_tgl_tran', '>=', $date->toDateString()];
                        }
                        break;
                    case 'sm_mercan_cair.akhir_tgl_tran':
                        $date = Carbon::createFromFormat('d-m-Y', $val);
                        if ($date) {
                            $filters[] = ['sm_mercan_cair.akhir_tgl_tran', '<=', $date->toDateString()];
                        }
                        break;
                    case 'sm_mercan_cair.TglTerima':
                        $date = Carbon::createFromFormat('d-m-Y', $val);
                        if ($date) {
                            $filters[] = ['sm_mercan_cair.TglTerima', '=', $date->toDateString(), 'whereDate'];
                        }
                        break;
                }
            };

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

        $whereAny = [];

        $select = array_unique([...$whereAny,
            'sm_mercan_cair.Nominal',
            'sm_mercan_cair.TglTerima',
            'sm_mercan_cair.dari_tgl_tran',
            'sm_mercan_cair.akhir_tgl_tran',
        ]);

        $query = DB::connection('DATA_MYSQL')->table('sm_mercan_cair')
            ->where('sm_mercan_cair.NamaPenerima', 'like', "%" . session('user.kantin') . "%")
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            })
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            });

        $totalRecords = $this->total();

        $totalRecordswithFilter =
            Cache::remember(
                CacheHandler::cacheKey($this->cacheKey, 'total_filtered_data', array_merge($filter, ['kantin' => (string) session('user.kantin')]), $searchValue ?? ''),
                now()->addMinutes(10),
                fn() => (clone $query)->count()
            );

        $records = (clone $query)
            ->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->toArray();

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
        );
        return response()->json($response);
    }

    public function total(): int
    {
        $key = Str::slug($this->cacheKey);
        return Cache::remember(
            "{$key}:total_all_data:" . Str::slug((string) session('user.kantin')),
            now()->addMinutes(10),
            fn() => DB::connection('DATA_MYSQL')->table('sm_mercan_cair')
                ->where('sm_mercan_cair.NamaPenerima', 'like', "%" . session('user.kantin') . "%")
                ->count()
        );
    }


}
