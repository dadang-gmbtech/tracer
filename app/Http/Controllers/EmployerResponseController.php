<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployerFormRequest;
use App\Models\Alumni;
use App\Models\EmployerResponse;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public "Form Pengguna Alumni" — accessed via a temporary signed URL (no
 * login), generated from TracerResponseController::shareEmployerLink().
 */
class EmployerResponseController extends Controller
{
    public function show(Alumni $alumni): View
    {
        return view('employer.form', [
            'alumni' => $alumni,
            'extraQuestions' => Question::active()->forTarget(Question::TARGET_EMPLOYER)->get(),
        ]);
    }

    public function store(EmployerFormRequest $request, Alumni $alumni): RedirectResponse
    {
        $response = EmployerResponse::create([...$request->validated(), 'alumni_id' => $alumni->id]);

        foreach ($request->input('extra', []) as $questionId => $value) {
            $response->questionAnswers()->create(['question_id' => $questionId, 'value' => $value]);
        }

        return redirect()->route('employer.thanks');
    }

    public function thanks(): View
    {
        return view('employer.thanks');
    }
}
