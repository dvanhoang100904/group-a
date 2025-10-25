@extends('layouts.app')

@section('content')
<div id="app">
    <folder-index 
        :initial-folders='@json($folders)'
        :current-folder='@json($currentFolder)'
        :breadcrumbs='@json($breadcrumbs)'
        success="{{ session('success') ?? '' }}"
        error="{{ session('error') ?? '' }}"
        :search-params='@json(request()->all())'
    ></folder-index>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush