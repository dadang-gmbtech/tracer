<?php

namespace App\Http\Controllers;

use App\Contracts\AcademicInfoServiceContract;
use App\Exports\TracerResponsesTemplateExport;
use App\Http\Requests\TracerFormRequest;
use App\Imports\TracerResponsesImport;
use App\Models\Alumni;
use App\Models\City;
use App\Models\Province;
use App\Models\Question;
use App\Models\TracerResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TracerResponseController extends Controller
{
    public function edit(Request $request, Alumni $alumni, AcademicInfoServiceContract $academicInfo): View
    {
        $this->authorize('fillTracer', $alumni);

        $alumni->load('tracerResponse', 'studyProgram');

        return view('tracer.edit', [
            'alumni' => $alumni,
            'identity' => $academicInfo->forAlumni($alumni),
            'response' => $alumni->tracerResponse ?? new TracerResponse,
            'extraQuestions' => Question::active()->forTarget(Question::TARGET_TRACER)->get(),
            'provinces' => Province::with('cities')->orderBy('name')->get(),
            'canEdit' => $request->user()->can('fillTracer', $alumni),
        ]);
    }

    public function update(TracerFormRequest $request, Alumni $alumni): RedirectResponse
    {
        $data = $request->validated();
        $data['f5a1'] = $data['work_province_id'] ? Province::find($data['work_province_id'])?->code : null;
        $data['f5a2'] = $data['work_city_id'] ? City::find($data['work_city_id'])?->code : null;
        $data['submitted_by_user_id'] = $request->user()->id;
        $data['submitted_at'] = now();

        $alumni->tracerResponse()->updateOrCreate(['alumni_id' => $alumni->id], $data);

        foreach ($request->input('extra', []) as $questionId => $value) {
            $alumni->questionAnswers()->updateOrCreate(
                ['question_id' => $questionId],
                ['value' => is_array($value) ? implode(', ', $value) : $value]
            );
        }

        return redirect()->route('alumni.show', $alumni)->with('status', 'Data tracer studi berhasil disimpan.');
    }

    public function shareEmployerLink(Request $request, Alumni $alumni): RedirectResponse
    {
        $this->authorize('view', $alumni);

        $url = URL::temporarySignedRoute('employer.show', now()->addDays(30), ['alumni' => $alumni->id]);

        // Redirects back to the tracer form rather than alumni.show: that's
        // the one page every role that can reach this action actually lands
        // on (alumni are sent straight there after login and never see
        // alumni.show at all — see User::postLoginUrl()).
        return redirect()->route('tracer.edit', $alumni)->with('employerLink', $url);
    }

    public function importForm(): View
    {
        Gate::authorize('import-data');

        return view('tracer.import');
    }

    public function template(): BinaryFileResponse
    {
        Gate::authorize('import-data');

        return Excel::download(new TracerResponsesTemplateExport, 'template-data-tracer.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('import-data');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        // TracerResponsesImport reads in chunks (see its chunkSize()), but a
        // large/complex real-world file can still push PhpSpreadsheet's peak
        // memory past PHP's default limit while parsing a chunk.
        ini_set('memory_limit', '2048M');
        set_time_limit(300);

        $import = new TracerResponsesImport($request->user());
        Excel::import($import, $request->file('file'));

        return redirect()->route('tracer.import.form')
            ->with('status', "{$import->created} alumni baru dibuat, {$import->updated} data tracer studi diperbarui.")
            ->with('importSkipped', $import->skipped);
    }
}
