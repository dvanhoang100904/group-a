<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Exports\KhoaExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Exception;

class KhoaController extends Controller
{
    /**
     * Chuẩn hoá text (trim, strip tags, collapse spaces)
     */
    protected function normalizeText(?string $value): string
    {
        if ($value === null) return '';
        $value = strip_tags($value);
        // normalize unicode spaces and collapse whitespace
        $value = preg_replace('/\p{Z}+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }

    /**
     * Kiểm tra page param
     */
    protected function validatePageParam(Request $request)
    {
        if ($request->has('page')) {
            $page = $request->query('page');
            if (!ctype_digit((string)$page) || intval($page) < 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Hiển thị danh sách Khoa / Bộ môn (mới nhất lên trước)
     */
    public function index(Request $request)
    {
        if (!$this->validatePageParam($request)) {
            return view('khoa.notfound', ['message' => 'Tham số trang (page) không hợp lệ.']);
        }

        $query = Department::query()->withCount('subjects');

        // tìm kiếm
        if ($request->filled('keyword')) {
            $keyword = $this->normalizeText($request->keyword);
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('code', 'like', '%' . $keyword . '%');
                });
            } else {
                return redirect()->route('khoa.index')->with('error', 'Từ khoá tìm kiếm không hợp lệ.');
            }
        }

        // sắp xếp: mới thêm lên đầu
        $khoas = $query->orderBy('created_at', 'DESC')->paginate(10)->appends($request->query());

        return view('khoa.index', compact('khoas'));
    }

    /**
     * Xuất Excel
     */
    public function exportExcel()
    {
        return Excel::download(new KhoaExport, 'danh_sach_khoa_bo_mon.xlsx');
    }

    /**
     * Xuất PDF
     */
    public function exportPDF()
    {
        $khoas = Department::withCount('subjects')->get();
        $pdf = Pdf::loadView('khoa.export_pdf', compact('khoas'));
        return $pdf->download('danh_sach_khoa_bo_mon.pdf');
    }

    /**
     * Form tạo mới
     */
    public function create()
    {
        return view('khoa.create');
    }

    /**
     * Lưu mới (transaction + check duplicate để tránh double-submit)
     */
    public function store(Request $request)
    {
        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));

        $request->merge(['name' => $name]); // giúp validate với trimmed value

        $request->validate([
            'name' => 'required|string|max:150|unique:departments,name',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Kiểm tra tồn tại lần cuối trước khi insert (tránh double-submit)
            if (Department::where('name', $name)->exists()) {
                DB::rollBack();
                return redirect()->back()->withInput($request->all())
                    ->with('error', 'Khoa / Bộ môn đã tồn tại (vui lòng kiểm tra lại).');
            }

            // Tạo mã an toàn (lock)
            $lastId = DB::table('departments')->lockForUpdate()->max('department_id');
            $next = $lastId ? $lastId + 1 : 1;
            $code = 'KHOA' . str_pad($next, 3, '0', STR_PAD_LEFT);

            Department::create([
                'code' => $code,
                'name' => $name,
                'description' => $description ?: null,
            ]);

            DB::commit();

            return redirect()->route('khoa.index')->with('success', 'Thêm Khoa / Bộ môn thành công!');
        } catch (QueryException $qe) {
            DB::rollBack();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi cơ sở dữ liệu khi lưu. Vui lòng thử lại.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi không xác định. Vui lòng thử lại.');
        }
    }

    /**
     * Hiển thị chi tiết (kèm số lượng môn và list môn)
     */
    public function show($id)
    {
        $khoa = Department::withCount('subjects')
            ->with('subjects')
            ->findOrFail($id);

        return view('khoa.show', compact('khoa'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $khoa = Department::findOrFail($id);
        return view('khoa.edit', compact('khoa'));
    }

    /**
     * Update (optimistic locking bằng updated_at)
     */
    public function update(Request $request, $id)
    {
        $khoa = Department::findOrFail($id);

        $name = $this->normalizeText($request->input('name'));
        $description = $this->normalizeText($request->input('description'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => 'required|string|max:150|unique:departments,name,' . $id . ',department_id',
            'description' => 'nullable|string|max:255',
        ]);

        // optimistic locking: client phải gửi updated_at hidden
        $clientUpdatedAt = $request->input('updated_at');
        if ($clientUpdatedAt) {
            $serverUpdatedAt = $khoa->updated_at ? $khoa->updated_at->toDateTimeString() : null;
            if ($serverUpdatedAt !== $clientUpdatedAt) {
                return redirect()->back()->withInput($request->all())
                    ->with('error', 'Bản ghi đã bị thay đổi. Vui lòng tải lại trang trước khi cập nhật.');
            }
        }

        try {
            $khoa->update([
                'name' => $name,
                'description' => $description ?: null,
            ]);

            return redirect()->route('khoa.index')->with('success', 'Cập nhật Khoa / Bộ môn thành công!');
        } catch (QueryException $qe) {
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi cơ sở dữ liệu khi cập nhật.');
        } catch (Exception $e) {
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Lỗi không xác định khi cập nhật.');
        }
    }

    /**
     * Xóa (chỉ chấp nhận method DELETE)
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->isMethod('delete')) {
            return redirect()->route('khoa.index')->with('error', 'Phương thức xóa không hợp lệ.');
        }

        $khoa = Department::findOrFail($id);
        $khoa->delete();

        return redirect()->route('khoa.index')->with('success', 'Xóa Khoa / Bộ môn thành công!');
    }
}
