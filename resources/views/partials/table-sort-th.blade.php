@php
    use App\Support\TableSort;

    $sortBy = $sortBy ?? 'id';
    $sortDir = $sortDir ?? 'asc';
    $href = route($routeName, TableSort::toggleQuery(request()->query(), $column));
    $active = $sortBy === $column ? ' is-active' : '';
    $extraClass = $class ?? '';
    if ($sortBy === $column) {
        $arrow = $sortDir === 'desc' ? '▼' : '▲';
    } else {
        $arrow = '↕';
    }
@endphp
<th class="tbl-th-sort{{ $active }}{{ $extraClass !== '' ? ' '.$extraClass : '' }}">
    <a href="{{ $href }}" title="Urutkan {{ $label }}">{{ $label }} <span class="tbl-sort-arrow">{{ $arrow }}</span></a>
</th>
