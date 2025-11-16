@extends('layouts.app')

@section('content')
<div id="app">
    <folder-index></folder-index>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/folder-import.js'])
@endpush