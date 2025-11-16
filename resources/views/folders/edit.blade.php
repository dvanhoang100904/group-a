@extends('layouts.app')

@section('content')
<div id="app">
    <folder-edit></folder-edit>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/folder-import.js'])
@endpush