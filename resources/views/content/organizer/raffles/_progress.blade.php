@php $p = $total ? round($sold * 100 / $total) : 0; @endphp
<div class="d-flex align-items-center gap-3">
  <div class="progress w-100" style="height: 8px">
    <div class="progress-bar" role="progressbar" style="width: {{ $p }}%"></div>
  </div>
  <small class="text-nowrap">{{ $sold }}/{{ $total }}</small>
</div>
