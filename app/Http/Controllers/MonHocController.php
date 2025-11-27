<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MonHocExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class MonHocController extends Controller
{
    /**
     * Helper: chuẩn hoá chuỗi input
     * - chuyển full-width digits sang ASCII
     * - chuyển full-width space (U+3000) và một số unicode spaces -> normal space
     * - strip_tags
     * - collapse nhiều khoảng trắng thành 1
     * - trim
     */
    protected function normalizeText(string $value): string
    {
        // chuyển full-width digits và chữ (nếu có)
        $fullWidth  = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９', '　'];
        $halfWidth  = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ' '];
        $value = str_replace($fullWidth, $halfWidth, $value);

        // loại bỏ thẻ html
        $value = strip_tags($value);

        // chuyển các unicode spaces (những common ones) thành space
        $value = preg_replace('/\p{Z}+/u', ' ', $value); // includes various separator spaces

        // collapse nhiều khoảng trắng thành 1
        $value = preg_replace('/\s+/u', ' ', $value);

        // trim
        $value = trim($value);

        return $value;
    }

    /**
     * Helper: chuẩn hoá number input
     * - chuyển full-width digits -> ascii
     * - remove non-digit chars
     */
    protected function normalizeNumber($value)
    {
        if (is_null($value)) return null;
        // chuyển sang string
        $s = (string)$value;
        $fullWidth  = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９'];
        $halfWidth  = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $s = str_replace($fullWidth, $halfWidth, $s);

        // loại bỏ mọi ký tự không phải digit
        $s = preg_replace('/[^\d]/', '', $s);

        return $s === '' ? null : intval($s);
    }

    /**
     * Kiểm tra page param hợp lệ (nếu có)
     */
    protected function validatePageParam(Request $request)
    {
        if ($request->has('page')) {
            $page = $request->query('page');
            // chỉ chấp nhận số nguyên dương
            if (!ctype_digit((string)$page) || intval($page) < 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * 📚 Hiển thị danh sách môn học
     */
    public function index(Request $request)
    {
        // kiểm tra page param
        if (!$this->validatePageParam($request)) {
            return view('monhoc.notfound', ['message' => 'Tham số trang (page) không hợp lệ.']);
        }

        $query = Subject::query()
            ->with('department')
            ->withCount('documents');

        // 🔍 Tìm kiếm theo tên hoặc mã - sử dụng normalize để ngăn paste HTML / full-width
        if ($request->filled('keyword')) {
            $keyword = $this->normalizeText($request->keyword);
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            } else {
                // nếu keyword sau normalize rỗng -> trả lỗi rõ ràng
                return redirect()->route('monhoc.index')->with('error', 'Từ khoá tìm kiếm không hợp lệ.');
            }
        }

        // 🏫 Lọc theo khoa / bộ môn (chặt chẽ hơn)
        if ($request->filled('department_id')) {
            $dep = $this->normalizeNumber($request->department_id);
            if (is_null($dep)) {
                return redirect()->route('monhoc.index')->with('error', 'Tham số bộ môn không hợp lệ.');
            }
            $query->where('department_id', $dep);
        }

        $monhocs = $query->orderBy('subject_id')->paginate(10)->appends($request->query());
        $departments = Department::orderBy('name')->get();

        return view('monhoc.index', compact('monhocs', 'departments'));
    }
     /**
     * 📤 Xuất Excel
     */
    public function exportExcel()
    {
        return Excel::download(new MonHocExport, 'danh_sach_mon_hoc.xlsx');
    }

    /**
     * 📄 Xuất PDF
     */
    public function exportPDF()
    {
        $monhocs = Subject::with('department')->get();

        $pdf = Pdf::loadView('monhoc.export_pdf', compact('monhocs'));

        return $pdf->download('danh_sach_mon_hoc.pdf');
    }

    /**
     * ➕ Trang thêm mới môn học
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('monhoc.create', compact('departments'));
    }

    /**
     * 💾 Lưu môn học mới
     * -> Đặt trong transaction để giảm rủi ro duplicate khi click nhiều lần
     */
    public function store(Request $request)
    {
        // normalize inputs first
        $input = [
            'name' => $request->has('name') ? $this->normalizeText($request->name) : '',
            'credits' => $this->normalizeNumber($request->credits),
            'department_id' => $this->normalizeNumber($request->department_id),
            'description' => $request->has('description') ? $this->normalizeText($request->description) : null,
        ];

        // custom validator (kiểm tra khoảng trắng, length, integer)
        $validator = Validator::make($input, [
            'name' => 'required|string|max:150|unique:subjects,name',
            'credits' => 'required|integer|min:1|max:10',
            'department_id' => 'required|integer|exists:departments,department_id',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Tên môn học là bắt buộc và không được để khoảng trắng.',
            'name.unique' => 'Tên môn học đã tồn tại.',
            'credits.required' => 'Tín chỉ là bắt buộc.',
            'credits.integer' => 'Tín chỉ phải là số nguyên từ 1 đến 10.',
            'department_id.exists' => 'Khoa / Bộ môn không tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        try {
            // tạo trong transaction
            DB::beginTransaction();

            // Sinh mã môn học an toàn (lấy max subject_id trong transaction)
            $lastId = DB::table('subjects')->lockForUpdate()->max('subject_id');
            $nextId = $lastId ? $lastId + 1 : 1;
            $code = 'MH' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

            // tạo
            Subject::create([
                'code' => $code,
                'name' => $input['name'],
                'credits' => $input['credits'],
                'department_id' => $input['department_id'],
                'description' => $input['description'],
            ]);

            DB::commit();

            return redirect()->route('monhoc.index')->with('success', 'Thêm môn học thành công!');
        } catch (QueryException $qe) {
            DB::rollBack();
            // duplicate or db error
            if ($qe->getCode() === '23000') {
                // constraint violation (duplicate)
                return redirect()->back()->withInput($request->all())
                    ->with('error', 'Tồn tại dữ liệu trùng lặp. Vui lòng kiểm tra lại hoặc tải lại trang trước khi lưu.');
            }
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi hệ thống khi lưu dữ liệu. Vui lòng thử lại.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi không xác định. Vui lòng thử lại.');
        }
    }

    /**
     * 👁 Xem chi tiết môn học
     */
    public function show($id)
    {
        // kiểm tra id là số
        if (!ctype_digit((string)$id)) {
            return view('monhoc.notfound', ['message' => 'ID không hợp lệ.']);
        }

        try {
            $monhoc = Subject::with(['department', 'documents'])->findOrFail($id);
            return view('monhoc.show', compact('monhoc'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('monhoc.notfound', ['message' => 'Môn học không tồn tại hoặc đã bị xóa.']);
        } catch (Exception $e) {
            return view('monhoc.notfound', ['message' => 'Lỗi khi truy xuất dữ liệu.']);
        }
    }

    /**
     * ✏️ Trang sửa môn học
     */
    public function edit($id)
    {
        if (!ctype_digit((string)$id)) {
            return view('monhoc.notfound', ['message' => 'ID không hợp lệ.']);
        }

        try {
            $monhoc = Subject::findOrFail($id);
            $departments = Department::orderBy('name')->get();
            return view('monhoc.edit', compact('monhoc', 'departments'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('monhoc.notfound', ['message' => 'Môn học không tồn tại hoặc đã bị xóa.']);
        } catch (Exception $e) {
            return view('monhoc.notfound', ['message' => 'Lỗi khi mở trang chỉnh sửa.']);
        }
    }

    /**
     * 🔄 Cập nhật môn học
     * - Có kiểm tra optimistic locking dựa trên updated_at (client phải gửi updated_at hidden field từ form edit)
     */
    public function update(Request $request, $id)
    {
        if (!ctype_digit((string)$id)) {
            return view('monhoc.notfound', ['message' => 'ID không hợp lệ.']);
        }

        try {
            $monhoc = Subject::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return view('monhoc.notfound', ['message' => 'Môn học không tồn tại hoặc đã bị xóa.']);
        }

        // chuẩn hoá input
        $input = [
            'name' => $request->has('name') ? $this->normalizeText($request->name) : '',
            'credits' => $this->normalizeNumber($request->credits),
            'department_id' => $this->normalizeNumber($request->department_id),
            'description' => $request->has('description') ? $this->normalizeText($request->description) : null,
        ];

        // validate
        $validator = Validator::make($input, [
            'name' => 'required|string|max:150|unique:subjects,name,' . $id . ',subject_id',
            'credits' => 'required|integer|min:1|max:10',
            'department_id' => 'required|integer|exists:departments,department_id',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Tên môn học là bắt buộc và không được để khoảng trắng.',
            'name.unique' => 'Tên môn học trùng với bản ghi khác.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        // optimistic locking: yêu cầu client phải gửi updated_at (ví dụ: <input type="hidden" name="updated_at" value="{{ $monhoc->updated_at }}">)
        $clientUpdatedAt = $request->input('updated_at');
        if ($clientUpdatedAt) {
            // so sánh chuỗi
            $serverUpdatedAt = $monhoc->updated_at ? $monhoc->updated_at->toDateTimeString() : null;
            if ($serverUpdatedAt !== $clientUpdatedAt) {
                return redirect()->back()->withInput($request->all())
                    ->with('error', 'Bản ghi đã bị thay đổi. Vui lòng tải lại trang trước khi cập nhật.');
            }
        }

        try {
            $monhoc->update([
                'name' => $input['name'],
                'credits' => $input['credits'],
                'department_id' => $input['department_id'],
                'description' => $input['description'],
            ]);

            return redirect()->route('monhoc.index')->with('success', 'Cập nhật môn học thành công!');
        } catch (QueryException $qe) {
            if ($qe->getCode() === '23000') {
                return redirect()->back()->withInput($request->all())
                    ->with('error', 'Xung đột dữ liệu. Vui lòng kiểm tra và thử lại.');
            }
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi hệ thống khi cập nhật dữ liệu.');
        } catch (Exception $e) {
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi không xác định khi cập nhật. Vui lòng thử lại.');
        }
    }

    /**
     * 🗑 Xóa môn học
     * - Bắt lỗi khi đã bị xóa
     * - Đảm bảo gọi qua method DELETE (route của bạn nên là Route::delete)
     */
    public function destroy(Request $request, $id)
    {
        // ưu tiên: chỉ cho phép method DELETE
        if (!$request->isMethod('delete')) {
            return redirect()->route('monhoc.index')->with('error', 'Phương thức xóa không hợp lệ.');
        }

        if (!ctype_digit((string)$id)) {
            return redirect()->route('monhoc.index')->with('error', 'ID không hợp lệ.');
        }

        try {
            $monhoc = Subject::findOrFail($id);

            // Optionally: kiểm tra quan hệ ràng buộc, ví dụ nếu có documents -> không xóa hoặc xử lý cascade theo business
            // if ($monhoc->documents()->count() > 0) {
            //     return redirect()->route('monhoc.index')->with('error', 'Không thể xóa môn học còn tài liệu liên kết.');
            // }

            $monhoc->delete();

            return redirect()->route('monhoc.index')->with('success', 'Xóa môn học thành công!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('monhoc.index')->with('error', 'Môn học đã bị xóa hoặc không tồn tại.');
        } catch (QueryException $qe) {
            return redirect()->route('monhoc.index')->with('error', 'Không thể xóa do ràng buộc dữ liệu.');
        } catch (Exception $e) {
            return redirect()->route('monhoc.index')->with('error', 'Lỗi khi xóa. Vui lòng thử lại.');
        }
    }
}
