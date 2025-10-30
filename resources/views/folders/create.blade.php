@extends('layouts.app')

@section('content')
<div id="app">
    <folder-create
        parent-folder-id="{{ $parentFolderId ?? '' }}"
        parent-folder-name="{{ $parentFolderName ?? 'Danh sách hiện tại' }}"
        :breadcrumbs='@json($breadcrumbs ?? [])'
        success="{{ session('success') ?? '' }}"
        error="{{ session('error') ?? ($error ?? '') }}"
    ></folder-create>
</div>
@endsection

@push('scripts')
   @vite(['resources/js/pages/folder-import.js'])
@endpush