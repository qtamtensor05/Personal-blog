<?php

namespace App\Http\Controllers;

use App\Modules\Templates\Queries\TemplateLibrary;
use Illuminate\Http\Request;

final class TemplateController extends Controller
{
    public function index(Request $request, TemplateLibrary $library)
    {
        $data = $request->validate(['q' => 'nullable|string|max:160', 'category' => 'nullable|integer']);

        return view('templates.index', $library->search($data['q'] ?? '', $data['category'] ?? ''));
    }

    public function show(int $template, TemplateLibrary $library)
    {
        $record = $library->find($template);

        return view('templates.preview', ['template' => $record, 'design' => json_decode($record->template_data, true)]);
    }

    public function choose(Request $request, int $template, TemplateLibrary $library)
    {
        $record = $library->find($template);
        $request->session()->put('selected_template', $record->id);
        $request->session()->put('url.intended', url('/workspace'));

        return redirect($request->user() ? '/workspace' : '/login')->with('status', 'Đã chọn mẫu '.$record->name.'.');
    }
}
