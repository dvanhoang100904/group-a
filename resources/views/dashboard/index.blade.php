@extends('layouts.app')

@section('content')
<div id="dashboard-app">
    <dashboard-component></dashboard-component>
</div>
@endsection

@push('scripts')
@vite(['resources/js/pages/dashboard.js'])