<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    // 📄 Trang upload (Blade)
    public function index()
    {
        return view('documents.Upload_Documents.Index_Upload');
    }

    // 📤 Upload file (Vue + Blade đều hoạt động)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:51200', // 50MB
            ]);

            $uploadPath = base_path('app/Public_UploadFile');

            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move($uploadPath, $fileName);

            // Nếu là AJAX (Vue)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Upload thành công!',
                    'file' => $fileName,
                    'path' => 'app/Public_UploadFile/' . $fileName
                ]);
            }

            // Nếu là form submit (Blade)
            return back()->with('success', 'Upload thành công! File đã lưu tại: ' . $fileName);
        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload thất bại: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Upload thất bại: ' . $e->getMessage());
        }
    }

    // 📦 Download file
    public function download($version)
    {
        $path = base_path('app/Public_UploadFile/' . $version);

        if (!File::exists($path)) {
            abort(404, 'Không tìm thấy file.');
        }

        return response()->download($path);
    }

    // 🗑️ Xóa file
    public function destroy($document)
    {
        $path = base_path('app/Public_UploadFile/' . $document);

        if (File::exists($path)) {
            File::delete($path);
            return response()->json(['success' => true, 'message' => 'Đã xóa file ' . $document]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy file.'], 404);
    }

    // 📜 Metadata (liệt kê file)
    public function getMetadata()
    {
        $dir = base_path('app/Public_UploadFile');
        if (!File::exists($dir)) {
            return response()->json([]);
        }

        $files = collect(File::files($dir))->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'updated' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        });

        return response()->json(['success' => true, 'files' => $files]);
    }
}
