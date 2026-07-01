<style>
    .tbl-th-sort a {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        white-space: nowrap;
    }
    .tbl-th-sort a:hover { color: #4f46e5; }
    .tbl-th-sort.is-active a { color: #4f46e5; font-weight: 700; }
    .tbl-sort-arrow {
        display: inline-block;
        font-size: 11px;
        line-height: 1;
        opacity: 0.55;
        min-width: 12px;
        text-align: center;
    }
    .tbl-th-sort.is-active .tbl-sort-arrow { opacity: 1; color: #4f46e5; }
    .tbl-th-sort a:hover .tbl-sort-arrow { opacity: 1; }
</style>
