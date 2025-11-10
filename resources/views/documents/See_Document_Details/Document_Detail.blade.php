@extends('layouts.app')

@section('title', 'Chi tiết tài liệu')

@section('content')
<div id="document-detail-app"></div>
@endsection

@push('scripts')
<script type="module" src="{{ mix('resources/js/pages/document-deltail.js') }}"></script>
@endpush
