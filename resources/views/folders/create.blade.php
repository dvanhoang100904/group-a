@extends('layouts.app')

@section('content')
<div id="app">
    <folder-create></folder-create>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/folder-import.js'])
@endpush