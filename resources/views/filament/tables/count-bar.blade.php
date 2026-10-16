@php
    $record = $getRecord();
    $n = (int) $record->officials_count;
    $pct = $max > 0 ? max($n > 0 ? 3 : 0, round($n / $max * 100)) : 0;
    $url = \App\Filament\Resources\Officials\OfficialResource::getUrl('index').'?tableFilters[part_id][value]='.$record->id;
@endphp
<a href="{{ $url }}" class="part-count" title="Xem {{ number_format($n) }} chứng điệp của giới đàn này">
    <span class="part-count-n {{ $n === 0 ? 'is-zero' : '' }}">{{ number_format($n) }}</span>
    <span class="part-count-bar"><i style="width: {{ $pct }}%"></i></span>
</a>
