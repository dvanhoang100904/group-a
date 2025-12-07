<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tags\StoreTagRequest;
use App\Http\Requests\Tags\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TagController extends Controller
{
    protected $imageFolder = 'public/tags';
    protected $defaultImage = 'images/default-tag.png'; // fallback nếu không có ảnh

    // ===================== Helpers =====================
    protected function normalizeText(?string $value): ?string
    {
        if ($value === null) return null;
        $value = preg_replace('/\p{Z}+/u', ' ', $value);
        $value = trim($value);

        // Convert full-width digits
        $map = [
            '０' => '0','１' => '1','２' => '2','３' => '3','４' => '4',
            '５' => '5','６' => '6','７' => '7','８' => '8','９' => '9'
        ];
        $value = strtr($value, $map);
        return $value;
    }

    protected function storeImageIfPresent(Request $request, ?Tag $tag = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $tag?->image;
        }

        $file = $request->file('image');
        $path = $file->store($this->imageFolder);

        // Xóa ảnh cũ nếu có
        if ($tag && $tag->image && Storage::exists($tag->image)) {
            try { Storage::delete($tag->image); } catch (\Throwable $e) { Log::warning('Delete old tag image failed', ['err'=>$e->getMessage()]); }
        }

        return $path;
    }

    // ===================== Index =====================
    public function index(Request $request)
    {
        $query = Tag::query()->withCount('documents');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filter === 'most_used') {
            $query->orderByDesc('documents_count');
        } else {
            $sortField = in_array($request->sort, ['created_at', 'name', 'documents_count']) ? $request->sort : 'created_at';
            $query->orderBy($sortField, 'desc');
        }

        $tags = $query->paginate(15)->withQueryString();
        return view('tags.index', compact('tags'));
    }

    // ===================== Create =====================
    public function create()
    {
        return view('tags.create');
    }

    // ===================== Store =====================
    public function store(StoreTagRequest $request)
    {
        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));

        if ($name === '' || mb_strlen($name) === 0) {
            return back()->withInput()->withErrors(['name' => 'Tên thẻ không được để trống.']);
        }

        try {
            $tag = null;
            DB::transaction(function () use (&$tag, $name, $description, $request) {
                $existing = Tag::where('name', $name)->lockForUpdate()->first();
                if ($existing) throw new \RuntimeException('exists');

                $imagePath = $this->storeImageIfPresent($request);

                $tag = Tag::create([
                    'name' => $name,
                    'description' => strip_tags($description),
                    'image' => $imagePath,
                ]);

                Log::info('Tag created', ['tag_id'=>$tag->id,'name'=>$tag->name,'user_id'=>auth()->id()]);
            });

            return redirect()->route('tags.index')->with('success','Thêm thẻ mới thành công!');
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'exists') {
                return back()->withInput()->withErrors(['name'=>'Tên thẻ đã tồn tại.']);
            }
            Log::error('Tag store runtime', ['err'=>$re->getMessage()]);
            return back()->withInput()->withErrors(['msg'=>'Lỗi khi tạo thẻ.']);
        } catch (\Throwable $e) {
            Log::error('Tag store unexpected', ['err'=>$e->getMessage()]);
            return back()->withInput()->withErrors(['msg'=>'Đã xảy ra lỗi khi tạo thẻ.']);
        }
    }

    // ===================== Show =====================
    public function show($id)
    {
        if (!ctype_digit((string)$id)) abort(404,'ID không hợp lệ.');

        $tag = Tag::find($id);
        if (!$tag) abort(404,'Thẻ không tồn tại.');

        return view('tags.show', compact('tag'));
    }

    // ===================== Edit =====================
    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }

    // ===================== Update =====================
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));

        if ($name === '' || mb_strlen($name) === 0) {
            return back()->withInput()->withErrors(['name'=>'Tên thẻ không được để trống.']);
        }

        if (mb_strlen($name) > 255) {
            return back()->withInput()->withErrors(['name'=>'Tên thẻ quá dài.']);
        }

        if ($description !== null && mb_strlen($description) > 1000) {
            return back()->withInput()->withErrors(['description'=>'Mô tả quá dài.']);
        }

        try {
            DB::transaction(function () use ($tag, $name, $description, $request) {
                $existing = Tag::where('name', $name)->where('id','!=',$tag->id)->lockForUpdate()->first();
                if ($existing) throw new \RuntimeException('exists');

                $data = [
                    'name' => $name,
                    'description' => strip_tags($description),
                ];

                // Upload ảnh nếu có
                if ($request->hasFile('image')) {
                    $data['image'] = $this->storeImageIfPresent($request, $tag);
                }

                $tag->update($data);
            });

            return redirect()->route('tags.index')->with('success','Cập nhật thẻ thành công!');
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'exists') {
                return back()->withInput()->withErrors(['name'=>'Tên thẻ đã tồn tại.']);
            }
            Log::error('Tag update runtime', ['err'=>$re->getMessage()]);
            return back()->withInput()->withErrors(['msg'=>'Cập nhật thất bại.']);
        } catch (\Throwable $e) {
            Log::error('Tag update unexpected', ['err'=>$e->getMessage()]);
            return back()->withInput()->withErrors(['msg'=>'Đã xảy ra lỗi khi cập nhật thẻ.']);
        }
    }

    // ===================== Destroy =====================
    public function destroy(Request $request, $id)
    {
        if (!$request->isMethod('delete')) {
            return redirect()->route('tags.index')->withErrors(['msg'=>'Phải dùng DELETE method.']);
        }

        if (!ctype_digit((string)$id)) {
            return redirect()->route('tags.index')->withErrors(['msg'=>'ID không hợp lệ.']);
        }

        $tag = Tag::find($id);
        if (!$tag) {
            return redirect()->route('tags.index')->withErrors(['msg'=>'Thẻ không tồn tại hoặc đã bị xóa.']);
        }

        try {
            DB::transaction(function () use ($tag) {
                if ($tag->documents()->count() > 0) throw new \RuntimeException('has_docs');

                if ($tag->image && Storage::exists($tag->image)) {
                    try { Storage::delete($tag->image); } catch (\Throwable $e) { Log::warning('Delete tag image failed',['err'=>$e->getMessage()]); }
                }

                $tag->delete();
                Log::info('Tag deleted',['tag_id'=>$tag->id,'user'=>auth()->id()]);
            });

            return redirect()->route('tags.index')->with('success','Xóa thẻ thành công!');
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'has_docs') {
                return redirect()->route('tags.index')->withErrors(['msg'=>'Không thể xóa: thẻ đang được dùng bởi tài liệu.']);
            }
            Log::error('Tag delete runtime',['err'=>$re->getMessage()]);
            return redirect()->route('tags.index')->withErrors(['msg'=>'Xóa thất bại.']);
        } catch (\Throwable $e) {
            Log::error('Tag delete unexpected',['err'=>$e->getMessage()]);
            return redirect()->route('tags.index')->withErrors(['msg'=>'Xóa thất bại.']);
        }
    }

    // ===================== Utility =====================
    public function imageUrl(Tag $tag): string
    {
        if ($tag->image && Storage::exists($tag->image)) {
            return Storage::url($tag->image);
        }
        return asset($this->defaultImage);
    }
}
