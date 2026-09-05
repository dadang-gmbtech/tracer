<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Question::class);

        return view('admin.questions.index', [
            'questions' => Question::orderBy('target')->orderBy('order')->paginate(30),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Question::class);

        return view('admin.questions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Question::class);

        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        Question::create($data);

        return redirect()->route('admin.questions.index')->with('status', 'Pertanyaan berhasil ditambahkan.');
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question);

        return view('admin.questions.edit', ['question' => $question]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $question->update($this->validated($request));

        return redirect()->route('admin.questions.index')->with('status', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $question->delete();

        return redirect()->route('admin.questions.index')->with('status', 'Pertanyaan berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'in:tracer,employer'],
            'label' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:text,number,radio,checkbox,select'],
            'options' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['options'] = $data['options']
            ? array_values(array_filter(array_map('trim', explode("\n", $data['options']))))
            : null;
        $data['order'] = $data['order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
