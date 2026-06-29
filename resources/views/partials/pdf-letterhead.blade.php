@php
    use App\Support\BrandLogo;

    $logoDataUri = $logoDataUri ?? BrandLogo::dataUri();
    $institutionName = $institutionName ?? "MA'HAD TAHFIDZ RAUDHATUL QUR'AN";
    $institutionSub = $institutionSub ?? 'Mojokerto, Jawa Timur';
    $docTitle = $docTitle ?? null;
@endphp
<div class="head-top">
    <table class="head-row">
        <tr>
            <td class="logo-cell">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="Logo {{ $institutionName }}">
                @endif
            </td>
            <td class="text-cell">
                <p class="yayasan">{{ $institutionName }}</p>
                <p class="sub">{{ $institutionSub }}</p>
            </td>
            <td class="logo-cell"></td>
        </tr>
    </table>
</div>
@if ($docTitle)
    <h1 class="title">{{ $docTitle }}</h1>
@endif
