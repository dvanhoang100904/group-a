@extends('layouts.app')

@section('title', 'Upload Tài Liệu')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Vue Component Mount Point -->
            <div id="document-upload"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/document-upload.js'])
@endpush