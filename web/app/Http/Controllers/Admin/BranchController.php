<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches.index', ['branches' => Branch::orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.branches.form', ['branch' => new Branch(['is_active' => true, 'opens_at' => '10:00', 'closes_at' => '22:00'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = 'branch-'.Str::lower(Str::random(6));
        Branch::create($data);

        return redirect()->route('admin.branches.index')->with('status', 'الفرع اتضاف.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.form', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request));

        return redirect()->route('admin.branches.index')->with('status', 'الفرع اتحفظ.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'area' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'map_url' => ['nullable', 'url', 'max:255'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort'] ??= 0;

        return $data;
    }
}
