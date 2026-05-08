<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubwebSave;
use App\Models\Subweb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubwebController extends Controller
{
    public function fetch(Request $request)
    {
        $current = $request->input('current', 1);
        $pageSize = $request->input('pageSize', 20);
        $subwebModel = Subweb::query();

        foreach ($request->input('filter', []) as $filter) {
            if (($filter['id'] ?? null) === 'domain' && ($filter['value'] ?? '') !== '') {
                $subwebModel->where('domain', 'like', '%' . $filter['value'] . '%');
            }
        }

        $subwebs = $subwebModel->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $current);

        return response()->json([
            'data' => $subwebs->items(),
            'total' => $subwebs->total(),
        ]);
    }

    public function save(SubwebSave $request)
    {
        try {
            if (!Subweb::create($request->validated())) {
                return $this->fail([500, '创建失败']);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $this->fail([500, '创建失败']);
        }

        return $this->success(true);
    }

    public function update(Request $request)
    {
        $params = $request->validate([
            'id' => 'required|integer',
            'status' => 'required|in:active,off,error',
        ]);

        $subweb = Subweb::find($params['id']);
        if (!$subweb) {
            return $this->fail([400202, '数据不存在']);
        }

        try {
            $subweb->update(['status' => $params['status']]);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->fail([500, '保存失败']);
        }

        return $this->success(true);
    }

    public function drop(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return $this->fail([422, 'ID不能为空']);
        }

        $subweb = Subweb::find($id);
        if (!$subweb) {
            return $this->fail([400202, '数据不存在']);
        }

        try {
            if (!$subweb->delete()) {
                return $this->fail([500, '删除失败']);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $this->fail([500, '删除失败']);
        }

        return $this->success(true);
    }
}
