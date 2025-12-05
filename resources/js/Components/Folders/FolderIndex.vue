<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header với nút Mới -->
    <div class="flex items-center justify-between mb-6">
      <!-- Nút Mới ở vị trí header -->
<!-- Nút Mới -->
<div class="flex-shrink-0 relative">
    <button @click="toggleNewDropdown"
            :disabled="!shouldShowNewFolderButton()"
            :title="!shouldShowNewFolderButton() ? 'Bạn không có quyền tạo thư mục/file trong folder này' : ''"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
        <i class="fas fa-plus mr-2"></i>Mới
        <i class="fas fa-chevron-down ml-2 text-xs"></i>
    </button>
    
    <!-- Dropdown menu -->
    <div v-if="showNewDropdown && shouldShowNewFolderButton()" class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-30">
        <button @click="openCreateFolder" 
                class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 w-full text-left text-gray-700">
            <i class="fas fa-folder-plus text-blue-500 mr-3"></i>Tạo thư mục
        </button>
        <a :href="sanitizeUrl(uploadFileUrl)" 
           class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 no-underline text-gray-700">
            <i class="fas fa-file-upload text-green-500 mr-3"></i>Tải file lên
        </a>
    </div>
    
    <!-- Thông báo khi không có quyền -->
    <div v-if="showNewDropdown && !shouldShowNewFolderButton()" 
         class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-30">
        <div class="px-4 py-2 text-sm text-gray-500 italic">
            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
            Bạn chỉ có quyền xem folder này
        </div>
    </div>
</div>

      <!-- Controls -->
      <div class="flex items-center gap-3">
        <span v-if="lastUpdate" class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded-lg hidden sm:block">
          <i class="fas fa-clock mr-2"></i>{{ formatTime(lastUpdate) }}
        </span>
        
        <button @click="manualReload" :disabled="loading"
                class="px-3 py-2 bg-white hover:bg-gray-100 border rounded-lg text-sm flex items-center transition-colors disabled:opacity-50">
          <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
          Làm mới
        </button>
        
        <button @click="toggleAutoReload"
                :class="['px-3 py-2 border rounded-lg text-sm flex items-center transition-colors',
                         autoReloadEnabled ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white text-gray-700 border-gray-300']">
          <i class="fas fa-clock mr-2"></i>
          {{ autoReloadEnabled ? 'Tự động: Bật' : 'Tự động: Tắt' }}
        </button>
      </div>
    </div>

    <!-- Actions & Search -->
    <div class="flex flex-col lg:flex-row gap-4 mb-6">
      <!-- Search Section -->
      <div class="flex flex-col lg:flex-row gap-4 mb-6">
        <div class="flex-1 min-w-0">
          <div class="flex flex-col sm:flex-row gap-2 bg-gray-100 rounded-lg shadow-sm border p-3">
            <div class="relative flex-1">
              <input v-model="searchParams.name" 
                     type="text" 
                     placeholder="Tìm theo tên" 
                     class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:outline-none"
                     @keyup.enter="handleSearch"
                     maxlength="255">
              <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            
            <div class="relative flex-1">
              <input v-model="searchParams.date" 
                     type="date" 
                     class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:outline-none"
                     @change="validateDate">
              <i class="fas fa-calendar absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            
            <!-- Lọc theo loại file -->
            <div class="relative flex-1 min-w-0">
              <select v-model="searchParams.file_type" 
                      class="pl-10 pr-8 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full appearance-none bg-white truncate"
                      :disabled="loadingDocumentTypes">
                <option value="">Tất cả loại file</option>
                <option value="folder">Thư mục</option>
                <option v-for="docType in documentTypes" 
        :key="docType.type_id" 
        :value="docType.name"  
        class="truncate">
  {{ sanitizeOutput(docType.name) }}  <!-- Chỉ sanitize hiển thị -->
</option>
              </select>
              <i class="fas fa-file-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
              <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            </div>
            
            <div class="flex gap-2">
              <button @click="handleSearch" 
                      :disabled="loading"
                      class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors disabled:opacity-50 flex-shrink-0">
                <i class="fas fa-search mr-2"></i>Tìm
              </button>
              
              <button v-if="hasActiveFilters" @click="resetFilters" 
                      class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                <i class="fas fa-times mr-2"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <nav v-if="breadcrumbs.length > 0 || !isSearchMode" class="flex items-center justify-between mb-6">
      <ol class="flex items-center space-x-2 text-sm">
        <!-- Root chỉ hiển thị khi KHÔNG ở chế độ tìm kiếm -->
        <li v-if="!isSearchMode">
          <button @click="goToRoot" class="text-blue-500 hover:text-blue-700 flex items-center">
            <i class="fas fa-home mr-1"></i>Root
          </button>
        </li>
        
        <!-- Breadcrumbs bình thường -->
        <template v-if="!isSearchMode">
          <li v-for="(crumb, idx) in breadcrumbs" :key="crumb.folder_id" class="flex items-center">
            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
            <button v-if="idx < breadcrumbs.length - 1" 
                    @click="goToFolder(crumb.folder_id)" 
                    class="text-blue-500 hover:text-blue-700">
              {{ sanitizeOutput(crumb.name) }}
            </button>
            <span v-else class="text-gray-600 font-medium">{{ sanitizeOutput(crumb.name) }}</span>
          </li>
        </template>
      </ol>

      <!-- Nút quay lại chỉ hiển thị khi có currentFolder và không ở chế độ tìm kiếm -->
      <button v-if="currentFolder && !isSearchMode" @click="goToParent" 
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
      </button>
    </nav>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Xác nhận xóa</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            Bạn có chắc muốn xóa <strong>{{ itemToDelete?.item_type === 'folder' ? 'thư mục' : 'tài liệu' }}</strong> 
            "<strong>{{ sanitizeOutput(itemToDelete?.name) }}</strong>"? Hành động này không thể hoàn tác.
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Hủy
            </button>
            <button @click="confirmDelete" 
                    class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg flex items-center transition-colors">
              <i class="fas fa-trash mr-2"></i>Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Thành công</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            {{ sanitizeOutput(successMessage) }}
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="continueAfterSuccess" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors">
              <i class="fas fa-sync-alt mr-2"></i>Tiếp tục
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Lỗi</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            {{ sanitizeOutput(errorMessage) }}
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="hideErrorModal" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Đóng
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Thông báo chế độ tìm kiếm -->
    <div v-if="isSearchMode && items.data.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
      <div class="flex items-center">
        <i class="fas fa-search text-blue-500 mr-3"></i>
        <div>
          <p class="text-blue-800 font-medium">Đang hiển thị kết quả tìm kiếm</p>
          <p class="text-blue-600 text-sm">Tìm thấy {{ items.total }} kết quả phù hợp</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-if="!loading" class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <table class="min-w-full">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
         <tr v-for="item in (items.data || [])" 
              :key="getItemKey(item)" 
              class="hover:bg-gray-50 transition-colors cursor-pointer"
              @contextmenu.prevent="showContextMenu($event, item)">
            
            <!-- Tên -->
 <td class="px-6 py-4">
            <div class="flex items-center" 
                 @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
              <i :class="getItemIcon(item)" class="text-lg mr-3 flex-shrink-0"></i>
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">
                  {{ sanitizeOutput(item?.name) || 'Unknown' }}
                </div>
                
                <!-- Vị trí thông minh: Hiển thị khác nhau tùy chế độ -->
                <div class="text-xs text-gray-500 mt-1">
                  <!-- Chế độ tìm kiếm: Hiển thị đường dẫn folder gọn -->
                  <div v-if="isSearchMode && item?.folder_path" 
                       class="flex items-center truncate">
                    <i class="fas fa-folder mr-1 text-yellow-500"></i>
                    <span class="truncate">
                      {{ formatFolderPath(item.folder_path) }}
                    </span>
                  </div>
                  
                  <!-- Chế độ bình thường: Hiển thị thông tin con cho folder -->
                  <div v-else-if="!isSearchMode && item && item.item_type === 'folder'" 
                       class="flex items-center space-x-3">
                    <span v-if="item.child_folders_count > 0 || item.documents_count > 0" 
                          class="flex items-center">
                      <i class="fas fa-folder-open mr-1"></i>
                      {{ item.child_folders_count }} thư mục, {{ item.documents_count }} file
                    </span>
                  </div>
                  
                  <!-- Chế độ bình thường: Hiển thị loại file cho document -->
                  <div v-else-if="!isSearchMode && item && item.item_type === 'document'">
                    <span class="flex items-center">
                      <i class="fas fa-file-alt mr-1"></i>
                      {{ sanitizeOutput(item?.type_name) || 'File' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </td>

            <!-- Các cột khác giữ nguyên -->
            <td class="px-6 py-4 whitespace-nowrap" @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
              <span class="text-sm text-gray-600">{{ sanitizeOutput(item?.type_name) || 'Unknown' }}</span>
            </td>

            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" 
                @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
              {{ item ? formatDateTime(item.created_at) : '' }}
            </td>
            
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="relative inline-block text-left action-dropdown-container">
                <button @click.stop="item && toggleMenu(item, $event)"
                        :disabled="!item"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                  <i class="fas fa-ellipsis-v text-gray-500"></i>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="(items.data || []).length === 0">
            <td :colspan="isSearchMode ? 6 : 5" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center">
                <i class="fas fa-search text-gray-400 text-4xl mb-4" v-if="isSearchMode"></i>
                <i class="fas fa-folder-open text-gray-400 text-4xl mb-4" v-else></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                  {{ isSearchMode ? 'Không tìm thấy kết quả' : 'Không có dữ liệu' }}
                </h3>
                <p class="text-gray-500 mb-4">
                  {{ isSearchMode ? 'Hãy thử với từ khóa khác hoặc điều chỉnh bộ lọc' : 'Hãy tạo thư mục hoặc tải file lên' }}
                </p>
                <button v-if="!isSearchMode" @click="openCreateFolder" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                  <i class="fas fa-plus mr-2"></i>Tạo thư mục
                </button>
                <button v-if="isSearchMode" @click="resetFilters" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                  <i class="fas fa-times mr-2"></i>Xóa bộ lọc
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Action Dropdown (fixed positioning) -->
   <div v-if="activeMenu" class="fixed bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 w-56 z-[10001] action-dropdown-fixed"
     :style="actionDropdownStyle">
  <div class="py-1">
    <!-- Hiển thị quyền hiện tại -->
    <div class="px-4 py-2 text-xs text-gray-500 border-b">
      <div class="font-medium">{{ getUserPermissionText(activeMenu) }}</div>
      <div class="text-xs mt-1">{{ getPermissionDetails(activeMenu) }}</div>
    </div>
    
    <!-- Nút Chia sẻ - CHỈ chủ sở hữu -->
    <button v-if="shouldShowShareButton(activeMenu)" 
            @click.stop="shareFolder(activeMenu)"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
      <i class="fas fa-share-alt mr-3 text-green-500"></i>Chia sẻ
    </button>
    
    <!-- Nút Chỉnh sửa - CHỈ khi có quyền sửa thông tin -->
    <button v-if="shouldShowEditButton(activeMenu)" 
            @click.stop="editFolder(activeMenu)"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
      <i class="fas fa-edit mr-3 text-blue-500"></i>Chỉnh sửa thông tin
    </button>
    
    <!-- Nút Xóa - CHỈ khi có quyền xóa -->
    <button v-if="shouldShowDeleteButton(activeMenu)" 
            @click.stop="showDeleteConfirmation(activeMenu)"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
      <i class="fas fa-trash mr-3 text-red-500"></i>Xóa
    </button>
    
    <!-- Thông báo đặc biệt cho folder được share với quyền edit -->
    <div v-if="activeMenu.is_shared_folder && !activeMenu.is_owner && activeMenu.user_permission === 'edit'" 
         class="px-4 py-2 text-sm text-blue-600 text-center bg-blue-50">
      <i class="fas fa-info-circle mr-1"></i>
      Bạn có thể: Tạo folder con, upload file
    </div>
  </div>
</div>

    <!-- Context Menu -->
    <div v-if="contextMenu.visible" class="context-menu" :style="contextMenuStyle">
  <div class="context-menu-header">
    <i :class="getItemIcon(contextMenu.item)" class="mr-2"></i>
    <span class="font-medium text-sm truncate">{{ sanitizeOutput(contextMenu.item.name) }}</span>
    <span v-if="contextMenu.item.is_shared_folder && !contextMenu.item.is_owner" 
          class="text-xs text-blue-600 ml-2">
      ({{ contextMenu.item.user_permission === 'edit' ? 'Chỉnh sửa nội dung' : 'Chỉ xem' }})
    </span>
  </div>
  <div class="py-2">
    <button @click="openContextItem" class="context-menu-item">
      <i :class="contextMenu.item.item_type === 'folder' ? 'fas fa-folder-open' : 'fas fa-eye'" 
         class="text-blue-500 mr-3" style="width: 16px;"></i>
      {{ contextMenu.item.item_type === 'folder' ? 'Mở thư mục' : 'Xem file' }}
    </button>
    
    <!-- ✅ SỬA: Nút Sửa cho folder - CHỈ khi có quyền sửa thông tin -->
    <button v-if="contextMenu.item.item_type === 'folder' && shouldShowEditButton(contextMenu.item)" 
            @click="editFolder(contextMenu.item)" 
            class="context-menu-item">
      <i class="fas fa-edit text-blue-500 mr-3" style="width: 16px;"></i>Chỉnh sửa thông tin
    </button>
    
    <!-- ✅ SỬA: Nút Chia sẻ - CHỈ chủ sở hữu -->
    <button v-if="contextMenu.item.item_type === 'folder' && shouldShowShareButton(contextMenu.item)" 
            @click="shareFolder(contextMenu.item)" 
            class="context-menu-item">
      <i class="fas fa-share-alt text-green-500 mr-3" style="width: 16px;"></i>Chia sẻ
    </button>
    
    <button v-if="contextMenu.item.item_type === 'document'" 
            @click="downloadDocument(contextMenu.item)" 
            class="context-menu-item">
      <i class="fas fa-download text-green-500 mr-3" style="width: 16px;"></i>Tải xuống
    </button>
    
    <div class="context-menu-divider"></div>
    
    <!-- ✅ SỬA: Nút Xóa - CHỈ khi có quyền xóa -->
    <button v-if="shouldShowDeleteButton(contextMenu.item)" 
            @click="showDeleteConfirmation(contextMenu.item)" 
            class="context-menu-item context-menu-item-danger w-full text-left">
      <i class="fas fa-trash text-red-500 mr-3" style="width: 16px;"></i>Xóa
    </button>
  </div>
</div>

    <div v-if="contextMenu.visible" class="context-menu-overlay" @click="hideContextMenu"></div>

    <!-- Pagination -->
    <div v-if="!loading && items.data.length > 0" 
         class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t">
      <div class="flex items-center text-sm text-gray-700">
        <span v-if="items.total > 0">
          Hiển thị <strong>{{ items.from }}-{{ items.to }}</strong> của <strong>{{ items.total }}</strong> kết quả
        </span>
        <span v-else>Không có kết quả</span>
      </div>

      <div v-if="items.last_page > 1" class="flex items-center space-x-2">
        <button v-if="items.current_page > 1" 
                @click="changePage(items.current_page - 1)"
                class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
          <i class="fas fa-chevron-left mr-1"></i>Trước
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          <i class="fas fa-chevron-left mr-1"></i>Trước
        </span>

        <button v-for="page in pages" 
                :key="page"
                @click="changePage(page)"
                :disabled="page === '...'"
                :class="['px-3 py-1 rounded-lg font-medium transition-colors', 
                        page === items.current_page 
                          ? 'bg-blue-500 text-white' 
                          : page === '...' 
                            ? 'cursor-default text-gray-500'
                            : 'bg-white border hover:bg-gray-50 text-gray-700']">
          {{ page }}
        </button>

        <button v-if="items.current_page < items.last_page" 
                @click="changePage(items.current_page + 1)"
                class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
          Sau<i class="fas fa-chevron-right ml-1"></i>
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          Sau<i class="fas fa-chevron-right ml-1"></i>
        </span>
      </div>
      <div v-else class="text-sm text-gray-500">
        Tất cả kết quả đang được hiển thị
      </div>

      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-700">Hiển thị:</span>
        <select v-model="perPage" @change="changePerPage" 
                class="border rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
    <!-- Share Folder Modal -->
<div v-if="showShareModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10002] p-4">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <i class="fas fa-share-alt text-blue-500 text-xl mr-3"></i>
          <h3 class="text-lg font-medium text-gray-900">Chia sẻ thư mục</h3>
        </div>
        <button @click="closeShareModal" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Folder Info -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="flex items-center">
          <i class="fas fa-folder text-yellow-500 mr-2"></i>
          <span class="font-medium text-gray-800">{{ sanitizeOutput(selectedFolder?.name) }}</span>
        </div>
      </div>

      <!-- Share Form -->
      <form @submit.prevent="shareFolderAction">
        <!-- Email Input -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email người dùng (phân cách bằng dấu phẩy)
          </label>
          <input 
            v-model="shareModalData.emailsInput"
            type="text" 
            placeholder="Nhập email1, email2, ..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
            :disabled="shareModalData.loading"
          >
          <p class="text-xs text-gray-500 mt-1">
            Nhập email của những người bạn muốn chia sẻ
          </p>
        </div>

        <!-- Permission Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Quyền truy cập
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input 
                v-model="shareModalData.permission" 
                type="radio" 
                value="view" 
                class="mr-3 text-blue-500 focus:ring-blue-500"
                :disabled="shareModalData.loading"
              >
              <div>
                <div class="font-medium text-gray-900">Chỉ xem</div>
                <div class="text-sm text-gray-500">Có thể xem và tải xuống file</div>
              </div>
            </label>
            <label class="flex items-center">
              <input 
                v-model="shareModalData.permission" 
                type="radio" 
                value="edit" 
                class="mr-3 text-blue-500 focus:ring-blue-500"
                :disabled="shareModalData.loading"
              >
              <div>
                <div class="font-medium text-gray-900">Chỉnh sửa</div>
                <div class="text-sm text-gray-500">Có thể thêm, sửa, xóa file và folder con</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Current Shares -->
        <div v-if="shareModalData.sharedUsers.length > 0" class="mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">Đang chia sẻ với:</h4>
          <div class="space-y-2">
            <div 
              v-for="user in shareModalData.sharedUsers" 
              :key="user.user_id"
              class="flex items-center justify-between bg-gray-50 rounded-lg p-2"
            >
              <div class="flex items-center">
                <i class="fas fa-user text-gray-400 mr-2"></i>
                <span class="text-sm font-medium">{{ sanitizeOutput(user.name) }}</span>
                <span class="text-xs text-gray-500 ml-2">({{ sanitizeOutput(user.email) }})</span>
                <span class="text-xs text-gray-500 ml-2">- {{ user.permission === 'edit' ? 'Chỉnh sửa' : 'Chỉ xem' }}</span>
              </div>
              <button 
                @click="unshareUser(user.user_id)"
                type="button"
                class="text-red-500 hover:text-red-700 text-sm"
                :disabled="shareModalData.loading"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button 
            type="button"
            @click="closeShareModal"
            :disabled="shareModalData.loading"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
          >
            Hủy
          </button>
          <button 
            type="submit"
            :disabled="shareModalData.loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors disabled:opacity-50"
          >
            <i class="fas fa-share-alt mr-2"></i>
            {{ shareModalData.loading ? 'Đang chia sẻ...' : 'Chia sẻ' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
  </div>
  
</template>

<script>
import axios from 'axios';

export default {
  name: 'FolderIndex',
  data() {
    return {
      items: { 
        data: [], 
        current_page: 1, 
        last_page: 1, 
        from: 0, 
        to: 0, 
        total: 0 
      },
      currentFolder: null,
      breadcrumbs: [],
      userInfo: null,
      loading: true,
      isSearchMode: false,
      activeMenu: null,
      perPage: 20,
      successMessage: '',
      errorMessage: '',
      showSuccessModal: false,
      showErrorModal: false, 
      showShareModal: false,
      searchParams: { 
        name: '', 
        date: '', 
        file_type: '' 
      },
      shareModalData: {
      emailsInput: '',
      permission: 'view',
      loading: false,
      sharedUsers: []
      },
      documentTypes: [],
      loadingDocumentTypes: false,
      contextMenu: { 
        visible: false, 
        x: 0, 
        y: 0, 
        item: null 
      },
      actionDropdownPosition: { 
        x: 0, 
        y: 0 
      },
      showNewDropdown: false,
      autoReloadInterval: null,
      autoReloadEnabled: false,
      lastUpdate: null,
      showDeleteModal: false,
      itemToDelete: null,
    }
  },
  computed: {
    safeItems() {
      return (this.items.data || []).filter(item => item !== null && item !== undefined);
    },
    hasActiveFilters() {
      return this.searchParams.name || this.searchParams.date || this.searchParams.file_type;
    },
uploadFileUrl() {
    if (!this.shouldShowNewFolderButton()) {
        return ''; // Không hiển thị link upload nếu không có quyền
    }
    
    const folderId = this.currentFolder?.folder_id || null;
    // ✅ BẢO MẬT: Sanitize folder ID trong URL
    const safeFolderId = folderId ? encodeURIComponent(folderId) : '';
    return `/upload?folder_id=${safeFolderId}`;
},
    pages() {
      const pages = [];
      const current = this.items.current_page;
      const last = this.items.last_page;
      const delta = 2;
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i);
      }
      
      if (current - delta > 2) pages.unshift('...');
      if (current + delta < last - 1) pages.push('...');
      
      pages.unshift(1);
      if (last > 1) pages.push(last);
      
      return pages.filter((v, i, a) => a.indexOf(v) === i);
    },
    
    actionDropdownStyle() {
      if (!this.activeMenu) return {};
      
      const menuWidth = 224;
      const menuHeight = 96;
      const padding = 10;
      
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      let x = this.actionDropdownPosition.x;
      let y = this.actionDropdownPosition.y;
      
      if (x + menuWidth > viewportWidth) {
        x = viewportWidth - menuWidth - padding;
      }
      
      if (y + menuHeight > viewportHeight) {
        y = this.actionDropdownPosition.y - menuHeight - 40;
      }
      
      x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
      y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
      
      return {
        left: x + 'px',
        top: y + 'px'
      };
    },

    contextMenuStyle() {
      if (!this.contextMenu.visible) return {};
      
      const menuWidth = 256;
      const menuHeight = 180;
      const padding = 10;
      
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      let x = this.contextMenu.x;
      let y = this.contextMenu.y;
      
      if (x + menuWidth > viewportWidth) {
        x = x - menuWidth;
      }
      
      if (y + menuHeight > viewportHeight) {
        y = y - menuHeight;
      }
      
      x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
      y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
      
      return {
        left: x + 'px',
        top: y + 'px'
      };
    },
  
    safePagination() {
        return {
          current_page: this.items.current_page || 1,
          last_page: this.items.last_page || 1,
          from: this.items.from || 0,
          to: this.items.to || 0,
          total: this.items.total || 0
      };
    }
  },
  mounted() {
    this.loadUserInfo();
    this.loadData();
    this.loadDocumentTypes(); 
    document.addEventListener('click', this.closeMenu);
    document.addEventListener('keydown', this.handleKeydown);
    document.addEventListener('click', this.closeNewDropdownOutside);
    document.addEventListener('scroll', this.handleScroll);
    window.addEventListener('resize', this.hideContextMenu);
  },
  beforeUnmount() {
    this.stopAutoReload();
    document.removeEventListener('click', this.closeMenu);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('click', this.closeNewDropdownOutside);
    document.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.hideContextMenu);
  },
  methods: {
     formatFolderPath(fullPath) {
      if (!fullPath) return 'Thư mục gốc';
      
      const pathParts = fullPath.split('/').filter(part => part.trim());
      
      if (pathParts.length <= 2) {
        return pathParts.join('/');
      }
      
      return `${pathParts[0]}/.../${pathParts[pathParts.length - 1]}`;
    },
    getItemTypeDisplay(item) {
      if (!item) return 'Unknown';
      
      if (item.item_type === 'folder') {
        return 'Thư mục';
      }
      
      return sanitizeOutput(item?.type_name) || 'Tài liệu';
    },
shouldShowEditButton(item) {
    if (item.item_type === 'folder') {
        // ✅ Folder của chính mình: được sửa
        // ✅ Folder con trong folder được share: được sửa
        // ❌ Folder được share trực tiếp (folder1): KHÔNG được sửa thông tin
        if (item.is_directly_shared && !item.is_owner) {
            return false; // ❌ KHÔNG được sửa tên folder1
        }
        return item.can_edit_info === true;
    }
    // Document: chỉ owner được sửa
    return item.is_owner;
},

shouldShowDeleteButton(item) {
    if (!item) return false;
    
    if (item.item_type === 'document') {
        return item.is_owner;
    }
    
    if (item.item_type === 'folder') {
        // ✅ Folder của chính mình: được xóa
        // ✅ Folder con trong folder được share: được xóa
        // ❌ Folder được share trực tiếp: KHÔNG được xóa
        if (item.is_directly_shared && !item.is_owner) {
            return false; // ❌ KHÔNG được xóa folder1
        }
        return item.can_delete === true;
    }
    
    return false;
},


shouldShowShareButton(item) {
    return item.is_owner && item.item_type === 'folder';
},

    getUserPermissionText(item) {
    if (item.is_owner) {
        return 'Chủ sở hữu';
    }
    if (item.is_directly_shared) {
        return `Được chia sẻ (${item.user_permission === 'edit' ? 'Có thể thêm folder con' : 'Chỉ xem'})`;
    }
    if (item.is_descendant_of_shared) {
        return `Folder con trong folder được share (${item.user_permission === 'edit' ? 'Có thể chỉnh sửa' : 'Chỉ xem'})`;
    }
    return 'Chỉ xem';
},


 // Thêm method kiểm tra quyền tạo folder con
shouldShowNewFolderButton() {
    // Luôn hiển thị nút "Mới" ở root (không có currentFolder)
    if (!this.currentFolder) {
        return true;
    }
    
    // Kiểm tra currentFolder
    if (this.currentFolder.is_shared_folder && !this.currentFolder.is_owner) {
        // ✅ SỬA: Chỉ được tạo folder con nếu có quyền edit
        // Kiểm tra cả user_permission và can_edit_content
        return this.currentFolder.user_permission === 'edit' || 
               this.currentFolder.can_edit_content === true;
    }
    
    // Folder của chính mình - luôn được phép
    return true;
},

    // Thêm method hiển thị thông báo quyền hạn chi tiết
getPermissionDetails(item) {
    if (item.is_owner) {
        return 'Bạn có toàn quyền với folder này';
    }
    if (item.is_directly_shared) {
        if (item.user_permission === 'edit') {
            return 'Bạn có thể: Tạo folder con, upload file, sửa/xóa nội dung bên trong. KHÔNG được: Sửa tên folder, xóa folder, chia sẻ folder.';
        }
        return 'Bạn chỉ có quyền xem folder này';
    }
    if (item.is_descendant_of_shared) {
        if (item.user_permission === 'edit') {
            return 'Bạn có toàn quyền với folder này (folder con trong folder được share)';
        }
        return 'Bạn chỉ có quyền xem folder này (folder con trong folder được share)';
    }
    return 'Bạn chỉ có quyền xem';
},

  /**
   * Mở modal chia sẻ folder
   */
  shareFolder(item) {
    // BẢO MẬT: Chỉ cho phép chủ sở hữu chia sẻ
    if (!item.is_owner) {
      this.showError('Chỉ chủ sở hữu mới có thể chia sẻ folder này');
      return;
    }
    
    this.selectedFolder = item;
    this.showShareModal = true;
    this.activeMenu = null;
    this.hideContextMenu();
    
    // Load danh sách người đã được chia sẻ
    this.loadSharedUsers();
  },

  /**
   * Tải danh sách người được chia sẻ
   */
async loadSharedUsers() {
  try {
    // ✅ THÊM /api/ prefix
    const response = await axios.get(`/api/folders/${this.selectedFolder.id}/shared-users`);
    if (response.data.success) {
      this.shareModalData.sharedUsers = response.data.data;
    }
  } catch (error) {
    console.error('Lỗi khi tải danh sách chia sẻ:', error);
  }
},
  /**
   * Chia sẻ folder với nhiều user
   */
  async shareFolderAction() {
  if (!this.shareModalData.emailsInput.trim()) {
    this.showError('Vui lòng nhập ít nhất một email');
    return;
  }
  const emails = this.shareModalData.emailsInput.split(',')
    .map(email => email.trim())
    .filter(email => email);

  console.log('Emails input:', this.shareModalData.emailsInput);
  console.log('Emails array:', emails);
  
  this.shareModalData.loading = true;
  try {
    const response = await axios.post(`/api/folders/${this.selectedFolder.id}/share`, {
      emails: emails,
      permission: this.shareModalData.permission
    });

    if (response.data.success) {
      this.showSuccess(response.data.message);
      this.shareModalData.emailsInput = '';
      this.loadSharedUsers();
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Lỗi khi chia sẻ folder';
    this.showError(message);
  } finally {
    this.shareModalData.loading = false;
  }
},
   /**
   * Hủy chia sẻ với user
   */
  async unshareUser(userId) {
  if (!confirm('Bạn có chắc muốn hủy chia sẻ với người dùng này?')) {
    return;
  }

  try {
     const response = await axios.post(`/api/folders/${this.selectedFolder.id}/unshare`, {
      user_ids: [userId]
    });

    if (response.data.success) {
      this.loadSharedUsers();
      this.showSuccess('Hủy chia sẻ thành công');
    }
  } catch (error) {
    this.showError('Lỗi khi hủy chia sẻ');
  }
},
   /**
   * Đóng modal chia sẻ
   */
  closeShareModal() {
    this.showShareModal = false;
    this.selectedFolder = null;
    this.shareModalData = {
      emailsInput: '',
      permission: 'view',
      loading: false,
      sharedUsers: []
    };
  },
  
// ---------------------------------------------------------------
    // ✅ BẢO MẬT: Sanitize input để tránh XSS
    sanitizeInput(value) {
      if (value === null || value === undefined) return '';
      const div = document.createElement('div');
      div.textContent = value.toString();
      return div.innerHTML.replace(/[^\w\s\-_.]/gi, '');
    },

    // ✅ BẢO MẬT: Sanitize output để tránh XSS
    sanitizeOutput(value) {
      if (value === null || value === undefined) return '';
      const div = document.createElement('div');
      div.textContent = value.toString();
      return div.innerHTML;
    },

    // ✅ BẢO MẬT: Sanitize URL để tránh XSS và injection
    sanitizeUrl(url) {
      if (!url) return '';
      try {
        const parsed = new URL(url, window.location.origin);
        // Chỉ cho phép các URL cùng origin hoặc đường dẫn tương đối
        if (parsed.origin === window.location.origin || parsed.protocol === 'about:') {
          return url;
        }
        return '';
      } catch {
        return '';
      }
    },

    // ✅ BẢO MẬT: Validate folder ID
    validateFolderId(folderId) {
      if (!folderId || !Number.isInteger(Number(folderId)) || folderId <= 0) {
        throw new Error('ID thư mục không hợp lệ');
      }
      return Number(folderId);
    },

    // ✅ BẢO MẬT: Validate date input
    validateDate() {
      if (this.searchParams.date) {
        const date = new Date(this.searchParams.date);
        if (isNaN(date.getTime())) {
          this.searchParams.date = '';
          this.showError('Ngày không hợp lệ');
        }
      }
    },

    goToFolder(folderId) {
      // ✅ BẢO MẬT: Validate folder ID
      try {
        const validFolderId = this.validateFolderId(folderId);
        
        if (this.isSearchMode) {
          this.isSearchMode = false;
          this.searchParams = { name: '', date: '', file_type: '' };
        }
        
        this.currentFolder = { folder_id: validFolderId };
        this.items.current_page = 1;
        this.loadData();
      } catch (error) {
        this.showError('ID thư mục không hợp lệ');
      }
    },

    // THÊM: Method load danh sách loại tài liệu
    async loadDocumentTypes() {
      this.loadingDocumentTypes = true;
      try {
    const response = await axios.get('/api/types');
    
    this.documentTypes = response.data || [];
    
  } catch (error) {
    // Fallback to empty array if API fails
    this.documentTypes = [];
    console.error('Error loading document types:', error);
  } finally {
    this.loadingDocumentTypes = false;
  }
    },

    getItemKey(item) {
      if (!item) return 'null-item';
      return `${item.item_type}-${item.id}`;
    },

    // ✅ THAY THẾ: Method tiếp tục sau khi thành công
    continueAfterSuccess() {
      this.showSuccessModal = false;
      this.successMessage = '';
      this.loadData();
    },

    // ✅ THAY THẾ: Method ẩn modal lỗi
    hideErrorModal() {
      this.showErrorModal = false;
      this.errorMessage = '';
    },

    // ✅ THAY THẾ: Hiển thị modal thành công
    showSuccess(message) {
      this.successMessage = this.sanitizeOutput(message);
      this.showSuccessModal = true;
    },

    // ✅ THAY THẾ: Hiển thị modal lỗi
    showError(message) {
      this.errorMessage = this.sanitizeOutput(message);
      this.showErrorModal = true;
    },

    async loadData() {
      this.loading = true;
      
      try {
        // ✅ BẢO MẬT: Sanitize search parameters
        const params = {
          name: this.sanitizeInput(this.searchParams.name || ''),
          date: this.searchParams.date || '',
          file_type: this.searchParams.file_type || '',
          per_page: this.perPage,
          page: this.items.current_page,
        };

        // ✅ QUAN TRỌNG: Chỉ thêm parent_id khi KHÔNG ở chế độ tìm kiếm
        if (this.currentFolder?.folder_id && !this.isSearchMode) {
          params.parent_id = this.currentFolder.folder_id;
        }

        const response = await axios.get('/api/folders', { params });
        
        if (response.data.success) {
          const data = response.data.data;
          
          this.items = {
            data: data.items?.data || data.items || [],
            current_page: data.items?.current_page || data.current_page || 1,
            last_page: data.items?.last_page || data.last_page || 1,
            from: data.items?.from || data.from || 0,
            to: data.items?.to || data.to || 0,
            total: data.items?.total || data.total || 0
          };
          
          this.currentFolder = data.currentFolder || null;
          this.breadcrumbs = data.breadcrumbs || [];
          this.isSearchMode = data.isSearchMode || false;
          this.lastUpdate = new Date().toISOString();
        } else {
          throw new Error(response.data.message || 'API response not successful');
        }
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Lỗi khi tải dữ liệu';
        this.showError(errorMsg);
        this.items = { data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
        this.currentFolder = null;
        this.breadcrumbs = [];
        this.isSearchMode = false;
      } finally {
        this.loading = false;
      }
    },

    exitSearchMode() {
      this.resetFilters();
    },

    // THÊM: Method refresh cả danh sách loại tài liệu
    async refreshDocumentTypes() {
      await this.loadDocumentTypes();
    },

    // CẬP NHẬT: Method manualReload để refresh cả document types
    async manualReload() {
      await Promise.all([
        this.loadDocumentTypes(),
        this.loadData()
      ]);
    },

    async loadUserInfo() {
      try {
        const userMeta = document.querySelector('meta[name="user-info"]');
        if (userMeta) {
          this.userInfo = JSON.parse(userMeta.getAttribute('content'));
        }
      } catch (error) {
        console.error('Error loading user info:', error);
      }
    },

    getItemIcon(item) {
      if (!item) return 'fas fa-question-circle text-gray-400';
      
      if (item.item_type === 'folder') {
        if (item.shared_info && !item.is_owner) {
          return 'fas fa-share-alt text-blue-500';
        }
        return 'fas fa-folder text-yellow-500';
      }
      
      const icons = {
        'PDF': 'fas fa-file-pdf text-red-500',
        'Word': 'fas fa-file-word text-blue-500',
        'Excel': 'fas fa-file-excel text-green-500',
        'PowerPoint': 'fas fa-file-powerpoint text-orange-500',
        'Image': 'fas fa-file-image text-purple-500',
        'Video': 'fas fa-file-video text-pink-500',
        'Audio': 'fas fa-file-audio text-indigo-500',
      };
      
      return icons[item.type_name] || 'fas fa-file text-gray-500';
    },

    goToParent() {
      if (this.currentFolder?.parent_folder_id) {
        this.goToFolder(this.currentFolder.parent_folder_id);
      } else {
        this.goToRoot();
      }
    },

    goToRoot() {
      this.currentFolder = null;
      this.items.current_page = 1;
      this.loadData();
    },

openCreateFolder() {
    // ✅ FIX: Lấy parent_id từ currentFolder (folder hiện tại đang xem)
    const parentId = this.currentFolder?.folder_id || null;
    
    console.log('📁 Creating folder with parent_id:', {
        currentFolder: this.currentFolder,
        parentId: parentId,
        currentFolderId: this.currentFolder?.folder_id
    });
    
    // ✅ BẢO MẬT: Sanitize URL
    const safeParentId = parentId ? encodeURIComponent(parentId) : '';
    
    // ✅ Tạo URL với parent_id
    const url = `/folders/create?parent_id=${safeParentId}`;
    console.log('📁 Redirecting to:', url);
    
    window.location.href = url;
    this.showNewDropdown = false;
},

    editFolder(item) {
      // ✅ BẢO MẬT: Validate item ID
      try {
        const validId = this.validateFolderId(item.id);
        window.location.href = `/folders/${validId}/edit`;
        this.activeMenu = null;
      } catch (error) {
        this.showError('ID không hợp lệ');
      }
    },

    openDocument(item) {
      if (item.file_path) {
        // ✅ BẢO MẬT: Sanitize URL trước khi mở
        const safeUrl = this.sanitizeUrl(item.file_path);
        if (safeUrl) {
          window.open(safeUrl, '_blank', 'noopener,noreferrer');
        } else {
          this.showError('Đường dẫn file không hợp lệ');
        }
      } else {
        this.showError('File không tồn tại');
      }
      this.hideContextMenu();
    },

    downloadDocument(item) {
      if (item.file_path) {
        // ✅ BẢO MẬT: Sanitize URL và filename
        const safeUrl = this.sanitizeUrl(item.file_path);
        const safeFilename = this.sanitizeInput(item.file_name || item.name);
        
        if (safeUrl) {
          const a = document.createElement('a');
          a.href = safeUrl;
          a.download = safeFilename;
          a.rel = 'noopener noreferrer';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
        } else {
          this.showError('Đường dẫn file không hợp lệ');
        }
      } else {
        this.showError('File không tồn tại');
      }
      this.activeMenu = null;
      this.hideContextMenu();
    },

    showDeleteConfirmation(item) {
      this.itemToDelete = item;
      this.showDeleteModal = true;
      this.activeMenu = null;
      this.hideContextMenu();
    },

    cancelDelete() {
      this.showDeleteModal = false;
      this.itemToDelete = null;
    },

    async confirmDelete() {
      if (!this.itemToDelete) return;
      
      try {
        // ✅ BẢO MẬT: Validate item ID
        const validId = this.validateFolderId(this.itemToDelete.id);
        
        const endpoint = this.itemToDelete.item_type === 'folder' 
          ? `/api/folders/${validId}`
          : `/api/documents/${validId}`;
        
        const response = await axios.delete(endpoint);
        
        if (response.data.success) {
          this.showSuccess(response.data.message);
          
          // Reset currentFolder if deleting current folder
          if (this.itemToDelete.item_type === 'folder' && 
              this.currentFolder && 
              this.currentFolder.folder_id === this.itemToDelete.id) {
            this.currentFolder = null;
          }
        }
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Lỗi khi xóa: ' + error.message;
        this.showError(errorMsg);
      } finally {
        this.showDeleteModal = false;
        this.itemToDelete = null;
      }
    },

    showContextMenu(event, item) {
      if (!item) return;
      
      event.preventDefault();
      event.stopPropagation();
      
      this.contextMenu = {
        visible: true,
        x: event.clientX,
        y: event.clientY,
        item: item
      };
      this.activeMenu = null;
    },

    toggleMenu(item, event) {
      if (!item) return;
      
      if (this.activeMenu && this.activeMenu.id === item.id && this.activeMenu.item_type === item.item_type) {
        this.activeMenu = null;
        return;
      }

      const button = event.target.closest('button');
      if (button) {
        const rect = button.getBoundingClientRect();
        this.actionDropdownPosition = {
          x: rect.right - 224,
          y: rect.bottom
        };
      }

      this.activeMenu = item;
      this.hideContextMenu();
    },

    hideContextMenu() {
      this.contextMenu = { 
        visible: false, 
        x: 0, 
        y: 0, 
        item: null 
      };
    },     

    closeMenu(event) {
      const isDropdown = event.target.closest('.action-dropdown-container');
      const isFixed = event.target.closest('.action-dropdown-fixed');
      
      if (!isDropdown && !isFixed) {
        this.activeMenu = null;
      }
    },

    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.activeMenu = null;
        this.hideContextMenu();
        if (this.showDeleteModal) {
          this.cancelDelete();
        }
        if (this.showSuccessModal) {
          this.continueAfterSuccess();
        }
        if (this.showErrorModal) {
          this.hideErrorModal();
        }
      }
    },

    handleScroll() {
      if (this.activeMenu) {
        this.activeMenu = null;
      }
    },

    closeNewDropdownOutside(event) {
      const dropdown = event.target.closest('.relative');
      if (this.showNewDropdown && !dropdown) {
        this.showNewDropdown = false;
      }
    },

    toggleNewDropdown() {
      this.showNewDropdown = !this.showNewDropdown;
    },

    openContextItem() {
      if (this.contextMenu.item.item_type === 'folder') {
        this.goToFolder(this.contextMenu.item.id);
      } else {
        this.openDocument(this.contextMenu.item);
      }
      this.hideContextMenu();
    },

    handleSearch() {
       this.items.current_page = 1;
  
      if (this.hasActiveFilters) {
        this.currentFolder = null;
        this.isSearchMode = true;
      }
      
      this.loadData();
    },

    resetFilters() {
      this.searchParams = { name: '', date: '', file_type: '' }; 
      this.items.current_page = 1;
      this.isSearchMode = false;
      this.loadData();
    },

    changePerPage() {
      this.items.current_page = 1;
      this.loadData();
    },

    changePage(page) {
      if (page === '...') return;
      this.items.current_page = page;
      this.loadData();
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('vi-VN');
    },

    formatDateTime(dateTimeString) {
      if (!dateTimeString) return '';
      const date = new Date(dateTimeString);
      return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    },

    formatTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleTimeString('vi-VN', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
      });
    },

    formatFileSize(bytes) {
      if (!bytes || bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    startAutoReload() {
      this.autoReloadInterval = setInterval(() => {
        if (this.autoReloadEnabled && !this.loading) {
          this.loadData();
        }
      }, 30000); // 30 seconds
    },

    stopAutoReload() {
      if (this.autoReloadInterval) {
        clearInterval(this.autoReloadInterval);
        this.autoReloadInterval = null;
      }
    },

    toggleAutoReload() {
      this.autoReloadEnabled = !this.autoReloadEnabled;
      if (this.autoReloadEnabled) {
        this.startAutoReload();
      } else {
        this.stopAutoReload();
      }
    },
  }
}
</script>

<style scoped>
/* Context Menu Styles */
.context-menu {
  position: fixed;
  z-index: 9999;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  animation: fadeInScale 0.15s ease-out;
  min-width: 240px;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

.context-menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9998;
  background: transparent;
}

.context-menu-header {
  padding: 12px 16px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  display: flex;
  align-items: center;
}

.context-menu-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 8px 16px;
  font-size: 14px;
  color: #374151;
  background: none;
  border: none;
  text-align: left;
  transition: all 0.15s ease;
  cursor: pointer;
  border-radius: 4px;
  margin: 2px 4px;
}

.context-menu-item:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  transform: translateX(2px);
}

.context-menu-item:hover i {
  color: white !important;
}

.context-menu-item-danger {
  color: #dc2626;
}

.context-menu-item-danger:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
}

.context-menu-divider {
  border-top: 1px solid #f1f5f9;
  margin: 6px 8px;
}

/* Action Dropdown Fixed */
.action-dropdown-fixed {
  position: fixed;
  z-index: 10001;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  animation: fadeInScale 0.15s ease-out;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

/* Animations */
@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-5px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Smooth transitions */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>