<?php

namespace App\Http\Controllers;

use App\Models\Official;
use Illuminate\Http\Request;

class OfficialController extends Controller
{
    /**
     * /official/{part}/{slug}-{id}
     * Tra cứu CHỈ theo id (giống NukeViet), nên QR cũ vẫn mở được dù slug/alias giới đàn có lệch chữ hoa-thường.
     */
    public function show(Request $request, string $part, string $slugId)
    {
        if (! preg_match('/^(?:[A-Za-z0-9\-]*?-)?(\d+)(?:\.html)?$/', $slugId, $m)) {
            abort(404);
        }

        $official = Official::active()->with('part')->find((int) $m[1]);
        abort_unless($official, 404);

        return view('official.detail', [
            'title' => $official->id . ' - ' . $official->phapdanh,
            'o' => $official,
            'isPrint' => $request->has('print'),
        ]);
    }

    /** Hỗ trợ link kiểu cũ: /index.php?language=vi&nv=official&op=... */
    public function legacy(Request $request)
    {
        // Dùng host tuyệt đối: khi vào qua /index.php, redirect('/x') sẽ bị chèn thêm "/index.php".
        $host = $request->getSchemeAndHttpHost();
        $op = trim((string) $request->query('op', ''), '/');

        // Chỉ link chi tiết (đích của QR) còn dùng; mọi link khác về trang chủ.
        if ($request->query('nv', 'official') === 'official' && preg_match('#^[^/]+/[^/]+$#', $op)) {
            return redirect($host . '/official/' . $op, 301);
        }

        return redirect($host . '/', 301);
    }
}
