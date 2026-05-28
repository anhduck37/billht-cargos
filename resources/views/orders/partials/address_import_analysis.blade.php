@php
    $analysisType = $analysis['type'] ?? 'unknown';
@endphp

@if($analysisType === 'new' || $analysisType === 'mixed')
    <div class="small text-success">
        <strong>Mới:</strong>
        {{ $analysis['new']['ward_name'] ?? 'N/A' }},
        {{ $analysis['new']['province_name'] ?? 'N/A' }}
    </div>
@endif

@if($analysisType === 'old' || $analysisType === 'mixed')
    <div class="small text-info">
        <strong>Cũ:</strong>
        {{ $analysis['old']['ward_name'] ?? 'N/A' }},
        {{ $analysis['old']['district_name'] ?? 'N/A' }},
        {{ $analysis['old']['city_name'] ?? 'N/A' }}
    </div>
@endif

@if($analysisType === 'unknown')
    <div class="small text-danger">
        Không nhận diện được
        @if(!empty($analysis['new']['errors']))
            <div>{{ implode(' ', $analysis['new']['errors']) }}</div>
        @endif
    </div>
@endif
