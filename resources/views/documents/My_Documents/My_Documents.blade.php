@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h2 class="text-2xl font-bold mb-4">📂 Quản lý tài liệu cá nhân</h2>

  <div id="document-list"></div>
</div>

@vite('resources/js/pages/document-list.js')
@endsection
