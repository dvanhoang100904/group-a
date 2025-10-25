@extends('layouts.app')

@section('content')
<div id="app">
    <folder-edit
        :folder='@json($folder)'
        :parent-folders='@json($parentFolders)'
        :initial-errors='@json($errors->getMessages())'
        :initial-old='@json(old())'
        success="{{ session('success') ?? '' }}"
        error="{{ session('error') ?? '' }}"
    ></folder-edit>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush