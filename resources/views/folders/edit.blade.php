@extends('layouts.app')

@section('content')
<div id="app">
    <folder-edit
        :folder='@json($folder)'
        :parent-folders='@json($parentFolders)'
        :descendant-ids='@json($descendantIds)'
        :breadcrumbs='@json($breadcrumbs ?? [])'
        success="{{ session('success') ?? '' }}"
        error="{{ session('error') ?? ($error ?? '') }}"
    ></folder-edit>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush