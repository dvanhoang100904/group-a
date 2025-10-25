@extends('layouts.app')

@section('content')
<div id="app">
    <folder-create
        parent-folder-id="{{ $parentFolderId }}"
        parent-folder-name="{{ $parentFolderName ?? 'Danh sách hiện tại' }}"
        :initial-errors='@json($errors->getMessages())'
        :initial-old='@json(old())'
        success="{{ session('success') ?? '' }}"
        error="{{ session('error') ?? '' }}"
    ></folder-create>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush