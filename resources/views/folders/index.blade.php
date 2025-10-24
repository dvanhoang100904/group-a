<<<<<<< HEAD
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Tiêu đề -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Quản Lý Thư Mục</h1>
    </div>

    <!-- Button thêm folder -->
    <div class="mb-6">
        <a href="{{ route('folders.create', ['parent_id' => $currentFolder ? $currentFolder->folder_id : null]) }}" 
           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center w-fit">
            <i class="fas fa-plus mr-2"></i>
            Tạo Thư Mục Mới
        </a>
    </div>

    <!-- Tìm kiếm & lọc -->
    <div class="mb-6">
        <form action="{{ route('folders.index') }}" method="GET" class="flex items-center space-x-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            @if($currentFolder)
                <input type="hidden" name="parent_id" value="{{ $currentFolder->folder_id }}">
            @endif
            
            <!-- Tìm theo tên -->
            <div class="relative">
                <input type="text" 
                       name="name" 
                       value="{{ request('name') }}"
                       placeholder="Tìm theo tên..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            
            <!-- Tìm theo ngày -->
            <div class="relative">
                <input type="date" 
                       name="date" 
                       value="{{ request('date') }}"
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-calendar absolute left-3 top-3 text-gray-400"></i>
            </div>
            
            <!-- Lọc theo trạng thái -->
            <div class="relative">
                <select name="status" 
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-40">
                    <option value="">Tất cả trạng thái</option>
                    <option value="public" {{ request('status') == 'public' ? 'selected' : '' }}>Công khai</option>
                    <option value="private" {{ request('status') == 'private' ? 'selected' : '' }}>Riêng tư</option>
                </select>
                <i class="fas fa-filter absolute left-3 top-3 text-gray-400"></i>
            </div>
            
            <!-- Nút tìm kiếm -->
            <button type="submit" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-search mr-2"></i>
                Tìm kiếm
            </button>
            
            <!-- Nút reset -->
            @if(request('name') || request('date') || request('status'))
            <a href="{{ $currentFolder ? route('folders.show', $currentFolder->folder_id) : route('folders.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-times mr-2"></i>
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Breadcrumbs và nút back -->
    <div class="mb-6">
        @if(!empty($breadcrumbs))
        <nav class="flex items-center justify-between mb-4">
            <!-- Breadcrumbs -->
            <div class="flex items-center">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="{{ route('folders.index') }}" class="text-blue-500 hover:text-blue-700 flex items-center">
                            <i class="fas fa-home mr-1"></i> Root
                        </a>
                    </li>
                    @foreach($breadcrumbs as $crumb)
                    <li class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        @if(!$loop->last)
                        <a href="{{ route('folders.show', $crumb->folder_id) }}" 
                           class="text-blue-500 hover:text-blue-700">
                            {{ $crumb->name }}
                        </a>
                        @else
                        <span class="text-gray-600 font-medium">{{ $crumb->name }}</span>
                        @endif
                    </li>
                    @endforeach
                </ol>
            </div>

            <!-- Nút back (chỉ hiển thị khi đang ở thư mục con) -->
            @if($currentFolder && $currentFolder->parent_folder_id)
            <button onclick="window.location='{{ route('folders.show', $currentFolder->parent_folder_id) }}'" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i>
                Quay lại
            </button>
            @elseif($currentFolder)
            <button onclick="window.location='{{ route('folders.index') }}'" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i>
                Về Root
            </button>
            @endif
        </nav>
        @endif
    </div>

    <!-- Thông báo kết quả tìm kiếm -->
    @if(request('name') || request('date') || request('status'))
    <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <span class="text-sm text-blue-700">
                    Kết quả tìm kiếm:
                    @if(request('name')) <strong>"{{ request('name') }}"</strong> @endif
                    @if(request('name') && (request('date') || request('status'))) và @endif
                    @if(request('date')) ngày <strong>{{ \Carbon\Carbon::parse(request('date'))->format('d/m/Y') }}</strong> @endif
                    @if(request('date') && request('status')) và @endif
                    @if(request('status')) 
                        <strong>
                            {{ request('status') == 'public' ? 'Công khai' : 'Riêng tư' }}
                        </strong>
                    @endif
                    - Tìm thấy <strong>{{ $folders->total() }}</strong> thư mục
                    (Trang {{ $folders->currentPage() }}/{{ $folders->lastPage() }})
                </span>
            </div>
            <a href="{{ $currentFolder ? route('folders.show', $currentFolder->folder_id) : route('folders.index') }}" 
               class="text-blue-500 hover:text-blue-700 text-sm">
                <i class="fas fa-times mr-1"></i> Xóa bộ lọc
            </a>
        </div>
    </div>
    @endif

    <!-- Thông báo -->
    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <span>{{ session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Thao tác
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($folders as $folder)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap cursor-pointer" 
                        onclick="window.location='{{ route('folders.show', $folder->folder_id) }}'">
                        <div class="flex items-center">
                            <i class="fas fa-folder text-yellow-500 mr-3 text-lg"></i>
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    @if(request('name'))
                                        {!! highlightText($folder->name, request('name')) !!}
                                    @else
                                        {{ $folder->name }}
                                    @endif
                                </div>
                                @if($folder->childFolders->count() > 0)
                                <div class="text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-folder-open mr-1"></i>
                                    {{ $folder->childFolders->count() }} thư mục con
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap cursor-pointer" 
                        onclick="window.location='{{ route('folders.show', $folder->folder_id) }}'">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $folder->status == 'public' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $folder->status == 'public' ? 'fa-globe' : 'fa-lock' }} mr-1"></i>
                            {{ $folder->status == 'public' ? 'Công khai' : 'Riêng tư' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer" 
                        onclick="window.location='{{ route('folders.show', $folder->folder_id) }}'">
                        {{ $folder->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer" 
                        onclick="window.location='{{ route('folders.show', $folder->folder_id) }}'">
                        {{ $folder->documents->count() }} files
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium align-middle">
                        <!-- Dropdown Menu -->
                        <div class="relative inline-block text-left">
                            <button type="button" 
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    id="menu-button-{{ $folder->folder_id }}"
                                    onclick="toggleMenu('{{ $folder->folder_id }}')">
                                <i class="fas fa-ellipsis-v text-gray-500"></i>
                            </button>
                            
                            <!-- Dropdown panel -->
                            <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
                                id="menu-{{ $folder->folder_id }}">
                                <div class="py-1" role="none">
                                    <!-- Edit -->
                                    <a href="{{ route('folders.edit', $folder->folder_id) }}" 
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                        <i class="fas fa-edit mr-3 text-blue-500"></i>
                                        Chỉnh sửa
                                    </a>
                                    
                                    <!-- Delete -->
                                    <form action="{{ route('folders.destroy', $folder->folder_id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                                onclick="return confirm('Bạn có chắc chắn muốn xóa thư mục {{ $folder->name }}?')">
                                            <i class="fas fa-trash mr-3 text-red-500"></i>
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <i class="fas fa-folder-open text-4xl mb-2"></i>
                            <p class="text-lg">
                                @if(request('name') || request('date') || request('status'))
                                    Không tìm thấy thư mục nào phù hợp
                                @else
                                    Không có thư mục nào
                                @endif
                            </p>
                            <p class="text-sm mt-1">
                                @if(request('name') || request('date') || request('status'))
                                    Hãy thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc
                                @else
                                    Hãy tạo thư mục đầu tiên
                                @endif
                            </p>
                            @if(request('name') || request('date') || request('status'))
                            <a href="{{ $currentFolder ? route('folders.show', $currentFolder->folder_id) : route('folders.index') }}" 
                               class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                                <i class="fas fa-times mr-2"></i>
                                Xóa bộ lọc
                            </a>
                            @else
                            <a href="{{ route('folders.create', ['parent_id' => $currentFolder ? $currentFolder->folder_id : null]) }}" 
                               class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Tạo Thư Mục Mới
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang và điều khiển hiển thị -->
    <div class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t border-gray-200">
        <!-- Thông tin số lượng -->
        <div class="flex items-center text-sm text-gray-700">
            <span>
                @if($folders->total() > 0)
                    Hiển thị 
                    <strong>{{ $folders->firstItem() }}-{{ $folders->lastItem() }}</strong>
                    của <strong>{{ $folders->total() }}</strong> kết quả
                @else
                    Không có kết quả nào
                @endif
            </span>
        </div>

        <!-- Các nút phân trang (chỉ hiển thị khi có nhiều trang) -->
        @if($folders->hasPages())
        <div class="flex items-center space-x-2">
            <!-- Nút trang trước -->
            @if($folders->onFirstPage())
            <span class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                <i class="fas fa-chevron-left mr-1"></i> Trước
            </span>
            @else
            <a href="{{ $folders->previousPageUrl() }}{{ request('name') || request('date') || request('status') ? '&' . http_build_query(request()->except('page')) : '' }}" 
               class="px-3 py-1 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
                <i class="fas fa-chevron-left mr-1"></i> Trước
            </a>
            @endif

            <!-- Các số trang -->
            @foreach($folders->getUrlRange(1, $folders->lastPage()) as $page => $url)
                @if($page == $folders->currentPage())
                <span class="px-3 py-1 bg-blue-500 text-white rounded-lg font-medium">
                    {{ $page }}
                </span>
                @else
                <a href="{{ $url }}{{ request('name') || request('date') || request('status') ? '&' . http_build_query(request()->except('page')) : '' }}" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
                    {{ $page }}
                </a>
                @endif
            @endforeach

            <!-- Nút trang sau -->
            @if($folders->hasMorePages())
            <a href="{{ $folders->nextPageUrl() }}{{ request('name') || request('date') || request('status') ? '&' . http_build_query(request()->except('page')) : '' }}" 
               class="px-3 py-1 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
                Sau <i class="fas fa-chevron-right ml-1"></i>
            </a>
            @else
            <span class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                Sau <i class="fas fa-chevron-right ml-1"></i>
            </span>
            @endif
        </div>
        @else
        <!-- Hiển thị thông báo khi chỉ có 1 trang -->
        <div class="text-sm text-gray-500">
            Tất cả kết quả đang được hiển thị
        </div>
        @endif

        <!-- Chọn số items mỗi trang (LUÔN HIỂN THỊ) -->
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-700">Hiển thị:</span>
            <select onchange="changePerPage(this.value)" 
                    class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </div>
</div>

<script>
    // Biến để theo dõi menu đang mở
    let currentOpenMenu = null;

    // JavaScript để toggle dropdown menu
    function toggleMenu(folderId) {
        const menu = document.getElementById('menu-' + folderId);
        
        // Nếu menu đang mở thì đóng lại
        if (currentOpenMenu === menu) {
            menu.classList.add('hidden');
            currentOpenMenu = null;
            return;
        }
        
        // Đóng menu đang mở trước đó
        if (currentOpenMenu) {
            currentOpenMenu.classList.add('hidden');
        }
        
        // Mở menu mới
        menu.classList.remove('hidden');
        currentOpenMenu = menu;
    }

    // Đóng menu khi click ra ngoài
    document.addEventListener('click', function(event) {
        const clickedElement = event.target;
        const isMenuButton = clickedElement.closest('[id^="menu-button-"]');
        const isInsideMenu = clickedElement.closest('[id^="menu-"]');
        
        // Nếu click không phải vào menu button và không phải trong menu
        if (!isMenuButton && !isInsideMenu && currentOpenMenu) {
            currentOpenMenu.classList.add('hidden');
            currentOpenMenu = null;
        }
    });

    // Ngăn sự kiện click trên menu lan ra ngoài
    document.querySelectorAll('[id^="menu-"]').forEach(menu => {
        menu.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    // Đóng menu khi nhấn phím Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && currentOpenMenu) {
            currentOpenMenu.classList.add('hidden');
            currentOpenMenu = null;
        }
    });

    // Thay đổi số items mỗi trang
    function changePerPage(perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('page'); // Quay về trang đầu tiên
        
        // Giữ lại parent_id nếu có
        @if($currentFolder)
        url.searchParams.set('parent_id', '{{ $currentFolder->folder_id }}');
        @endif
        
        window.location.href = url.toString();
    }
</script>

<style>
    [id^="menu-"] {
        z-index: 11111 !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* Highlight text trong kết quả tìm kiếm */
    .highlight {
        background-color: #ffeb3b;
        padding: 0 2px;
        border-radius: 2px;
        font-weight: bold;
    }
</style>
@endsection

<!DOCTYPE html>
<html lang="vi">
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
</html>
