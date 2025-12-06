<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tags\StoreTagRequest;
use App\Http\Requests\Tags\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    // Đường dẫn folder lưu ảnh
    protected $imageFolder = 'public/tags';
    protected $defaultImage = 'images/default-tag.png'; // public disk (public/) hoặc storage link

    public function __construct()
    {
        // Không ép middleware 'prevent.double.submit' để tránh lỗi container nếu chưa register.
        // Nếu muốn bật chống double submit, hãy dùng DB unique constraint + transaction (đã có).
    }

    // ============= Helpers =============
    protected function normalizeText(?string $value): ?string
    {
        if ($value === null) return null;

        // Replace full width spaces U+3000 and other unicode spaces -> normal space, then trim
        $value = preg_replace('/\p{Z}+/u', ' ', $value);
        $value = trim($value);

        // Convert full-width digits to ASCII digits
        $map = [
            '０' => '0','１' => '1','２' => '2','３' => '3','４' => '4',
            '５' => '5','６' => '6','７' => '7','８' => '8','９' => '9'
        ];
        $value = strtr($value, $map);

        // Optionally strip tags but keep basic formatting: we will strip tags at saving step
        return $value;
    }

    protected function normalizeNumberField($value)
    {
        if (is_null($value)) return null;
        return preg_replace_callback('/[０-９]/u', function($m){
            $map = ['０'=>'0','１'=>'1','２'=>'2','３'=>'3','４'=>'4','５'=>'5','６'=>'6','７'=>'7','８'=>'8','９'=>'9'];
            return $map[$m[0]] ?? $m[0];
        }, (string)$value);
    }

    protected function storeImageIfPresent(Request $request, Tag $tag = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $tag ? $tag->image : null;
        }

        $file = $request->file('image');

        // Store with unique name
        $path = $file->store($this->imageFolder);

        // If updating, delete old file
        if ($tag && $tag->image && Storage::exists($tag->image)) {
            try { Storage::delete($tag->image); } catch (\Throwable $e) { Log::warning('Delete old tag image failed', ['err'=>$e->getMessage()]); }
        }

        return $path;
    }

    // ============= Index =============
    public function index(Request $request)
    {
        // Page param validation
        if ($request->has('page') && !ctype_digit((string)$request->page)) {
            return abort(400, 'Tham số page không hợp lệ');
        }

        $query = Tag::query()->withCount('documents');

        // Search name (normalize user input)
        if ($request->filled('name')) {
            $name = $this->normalizeText($request->input('name'));
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($request->filter === 'most_used') {
            $query->orderBy('documents_count', 'desc');
        }

        switch ($request->sort) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'documents_count':
                $query->orderBy('documents_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $tags = $query->paginate(15)->withQueryString();

        return view('tags.index', compact('tags'));
    }

    // ============= Create =============
    public function create()
    {
        return view('tags.create');
    }

    // ============= Store =============
    public function store(StoreTagRequest $request)
    {
        // Normalize inputs
        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));

        // Extra validation guard: empty after trim/spaces
        if ($name === '' || mb_strlen($name) === 0) {
            return back()->withInput()->withErrors(['name' => 'Tên thẻ không được để trống hoặc chỉ chứa khoảng trắng.']);
        }

        // Limit length server-side (defensive)
        if (mb_strlen($name) > 255) {
            return back()->withInput()->withErrors(['name' => 'Tên thẻ quá dài.']);
        }
        if ($description !== null && mb_strlen($description) > 1000) {
            return back()->withInput()->withErrors(['description' => 'Mô tả quá dài.']);
        }

        try {
            $tag = null;
            DB::transaction(function () use (&$tag, $name, $description, $request) {
                // Double check exist (prevent race via unique constraint + transaction)
                $existing = Tag::where('name', $name)->lockForUpdate()->first();
                if ($existing) {
                    throw new \RuntimeException('exists');
                }

                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store($this->imageFolder);
                }

                $tag = Tag::create([
                    'name' => $name,
                    'description' => strip_tags($description),
                    'image' => $imagePath,
                ]);

                Log::info('Tag created', [
                    'tag_id' => $tag->id,
                    'name' => $tag->name,
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                ]);
            });

            return redirect()->route('tags.index')->with('success', 'Thêm thẻ mới thành công!');
        } catch (QueryException $qe) {
            // DB unique constraint violation (MySQL/SQLite SQLSTATE 23000)
            Log::warning('Tag store QueryException', ['err' => $qe->getMessage()]);
            return back()->withInput()->withErrors(['name' => 'Tên thẻ đã tồn tại hoặc lỗi cơ sở dữ liệu.']);
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'exists') {
                return back()->withInput()->withErrors(['name' => 'Tên thẻ đã tồn tại.']);
            }
            Log::error('Tag store runtime error', ['err'=>$re->getMessage()]);
            return back()->withInput()->withErrors(['msg' => 'Lỗi khi tạo thẻ.']);
        } catch (\Throwable $e) {
            Log::error('Tag store unexpected', ['err' => $e->getMessage()]);
            return back()->withInput()->withErrors(['msg' => 'Đã xảy ra lỗi khi tạo thẻ.']);
        }
    }

    // ============= Show =============
    public function show($id)
    {
        if (!ctype_digit((string)$id)) {
            abort(404, 'Không tìm thấy trang.');
        }

        $tag = Tag::find($id);
        if (!$tag) {
            abort(404, 'Không tìm thấy thẻ.');
        }

        return view('tags.show', compact('tag'));
    }

    // ============= Edit =============
    public function edit($id)
    {
        if (!ctype_digit((string)$id)) {
            return redirect()->route('tags.index')->withErrors(['msg' => 'Không tìm thấy trang.']);
        }

        $tag = Tag::find($id);
        if (!$tag) {
            return redirect()->route('tags.index')->withErrors(['msg' => 'Thẻ không tồn tại.']);
        }

        return view('tags.edit', compact('tag'));
    }

    // ============= Update =============
    public function update(UpdateTagRequest $request, $id)
    {
        if (!ctype_digit((string)$id)) {
            return redirect()->back()->withErrors(['msg' => 'ID không hợp lệ.']);
        }

        $tag = Tag::find($id);
        if (!$tag) {
            return redirect()->back()->withErrors(['msg' => 'Thẻ không tồn tại.']);
        }

        // Optimistic lock: client must send client_updated_at (ISO8601)
        $clientTs = $request->input('client_updated_at');
        if ($clientTs && $tag->updated_at && $tag->updated_at->toIso8601String() !== $clientTs) {
            return back()->withInput()->withErrors(['msg' => 'Dữ liệu đã thay đổi — vui lòng tải lại trang trước khi cập nhật.']);
        }

        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));

        if ($name === '' || mb_strlen($name) === 0) {
            return back()->withInput()->withErrors(['name' => 'Tên thẻ không được để trống hoặc chỉ chứa khoảng trắng.']);
        }

        if (mb_strlen($name) > 255) {
            return back()->withInput()->withErrors(['name' => 'Tên thẻ quá dài.']);
        }

        try {
            DB::transaction(function () use ($tag, $name, $description, $request) {
                // check duplicate other than current
                $exists = Tag::where('name', $name)->where('id', '!=', $tag->id)->lockForUpdate()->first();
                if ($exists) {
                    throw new \RuntimeException('exists');
                }

                // handle image: if provided, store and delete old one
                if ($request->hasFile('image')) {
                    $path = $request->file('image')->store($this->imageFolder);
                    if ($tag->image && Storage::exists($tag->image)) {
                        try { Storage::delete($tag->image); } catch (\Throwable $e) { Log::warning('Delete old image failed', ['err'=>$e->getMessage()]); }
                    }
                    $tag->image = $path;
                }

                $tag->name = $name;
                $tag->description = strip_tags($description);
                $tag->save();

                Log::info('Tag updated', ['tag_id' => $tag->id, 'user' => auth()->id(), 'ip' => request()->ip()]);
            });

            return redirect()->route('tags.index')->with('success', 'Cập nhật thẻ thành công!');
        } catch (QueryException $qe) {
            Log::warning('Tag update QueryException', ['err'=>$qe->getMessage()]);
            return back()->withInput()->withErrors(['name' => 'Cập nhật thất bại — có thể trùng tên hoặc lỗi DB.']);
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'exists') {
                return back()->withInput()->withErrors(['name' => 'Tên thẻ đã tồn tại.']);
            }
            Log::error('Tag update runtime', ['err'=>$re->getMessage()]);
            return back()->withInput()->withErrors(['msg' => 'Lỗi khi cập nhật thẻ.']);
        } catch (\Throwable $e) {
            Log::error('Tag update unexpected', ['err'=>$e->getMessage()]);
            return back()->withInput()->withErrors(['msg' => 'Đã xảy ra lỗi khi cập nhật thẻ.']);
        }
    }

    // ============= Destroy =============
    public function destroy(Request $request, $id)
    {
        // Bắt buộc dùng method DELETE
        if (!$request->isMethod('delete')) {
            return redirect()->route('tags.index')->withErrors(['msg' => 'Phải dùng phương thức DELETE để xóa.']);
        }

        if (!ctype_digit((string)$id)) {
            return redirect()->route('tags.index')->withErrors(['msg' => 'ID không hợp lệ.']);
        }

        $tag = Tag::find($id);
        if (!$tag) {
            return redirect()->route('tags.index')->withErrors(['msg' => 'Thẻ không tồn tại hoặc đã bị xóa.']);
        }

        try {
            DB::transaction(function () use ($tag) {
                // If there are related documents, you may prevent delete (business rule)
                // Example: if ($tag->documents()->count() > 0) { throw new \RuntimeException('has_docs'); }

                // Delete image file if exists
                if ($tag->image && Storage::exists($tag->image)) {
                    try { Storage::delete($tag->image); } catch (\Throwable $e) { Log::warning('Delete tag image failed', ['err'=>$e->getMessage()]); }
                }

                $tag->delete();

                Log::info('Tag deleted', ['tag_id' => $tag->id, 'user' => auth()->id(), 'ip' => request()->ip()]);
            });

            return redirect()->route('tags.index')->with('success', 'Xóa thẻ thành công!');
        } catch (\RuntimeException $re) {
            if ($re->getMessage() === 'has_docs') {
                return redirect()->route('tags.index')->withErrors(['msg' => 'Không thể xóa: thẻ đang được dùng bởi tài liệu.']);
            }
            Log::error('Tag delete runtime', ['err'=>$re->getMessage()]);
            return redirect()->route('tags.index')->withErrors(['msg' => 'Xóa thất bại.']);
        } catch (\Throwable $e) {
            Log::error('Tag delete unexpected', ['err'=>$e->getMessage()]);
            return redirect()->route('tags.index')->withErrors(['msg' => 'Xóa thất bại.']);
        }
    }

    // ============= Utility for views =============
    // Trả về đường dẫn ảnh (dùng trong view)
    public function imageUrl(Tag $tag): string
    {
        if ($tag->image && Storage::exists($tag->image)) {
            return Storage::url($tag->image);
        }
        // fallback to default
        return asset($this->defaultImage);
    }
}
