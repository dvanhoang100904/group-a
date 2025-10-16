@extends('layouts.app')

@section('content')
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thư Mục</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Thêm sau thẻ <body> -->
@if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        <div class="flex">
            <div class="py-1">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
            </div>
            <div>
                <p class="font-bold">Thành công!</p>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <div class="flex">
            <div class="py-1">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            </div>
            <div>
                <p class="font-bold">Lỗi!</p>
                <p>{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Quản Lý Thư Mục</h1>
            <a href="{{ route('folders.create') }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Tạo Thư Mục Mới
            </a>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tên thư mục
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Chỉ số
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ngày tạo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kích cỡ tập tin
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($folders as $folder)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-folder text-yellow-500 mr-3"></i>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $folder->name }}</div>
                                    @if($folder->childFolders->count() > 0)
                                    <div class="text-xs text-gray-500">
                                        {{ $folder->childFolders->count() }} thư mục con
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $folder->status == 'public' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $folder->status == 'public' ? 'Công khai' : 'Riêng tư' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $folder->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $folder->documents->count() }} files
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                            Không có thư mục nào. 
                            <a href="{{ route('folders.create') }}" class="text-blue-500 hover:text-blue-700 ml-1">
                                Tạo thư mục đầu tiên
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
@endsection