@php $__icons = \App\Support\WebSettings::icons(); @endphp
    <link rel="icon" href="{{ $__icons['ico'] }}" sizes="48x48">
@if($__icons['svg'])
    <link rel="icon" href="{{ $__icons['svg'] }}" type="image/svg+xml">
@endif
    <link rel="icon" href="{{ $__icons['32'] }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ $__icons['180'] }}">
    <link rel="manifest" href="{{ route('manifest') }}">
