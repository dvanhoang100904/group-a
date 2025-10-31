@extends('layouts.app')

@section('content')
<div id="app">
    <folder-index 
        :initial-folders='@json($folders)'
        :current-folder='@json($currentFolder)'
        :breadcrumbs='@json($breadcrumbs)'
        :search-params='@json(request()->all())'
    ></folder-index>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/folder-import.js'])
@endpush