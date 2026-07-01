@php
    use App\Support\TableSort;

    $sortBy = $sortBy ?? 'nocust';
    $sortDir = $sortDir ?? 'asc';
    $sortQuery = request()->query();
    $sortLink = static function (string $column) use ($sortQuery) {
        return route('master.data_siswa', TableSort::toggleQuery($sortQuery, $column));
    };
    $sortIcon = static function (string $column) use ($sortBy, $sortDir) {
        return TableSort::iconClass($column, $sortBy, $sortDir);
    };
    $sortActive = static function (string $column) use ($sortBy) {
        return $sortBy === $column ? ' is-active' : '';
    };
@endphp
