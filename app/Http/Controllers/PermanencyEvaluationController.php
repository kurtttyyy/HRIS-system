<?php

namespace App\Http\Controllers;

use App\Models\PermanencyEvaluation;
use App\Models\PermanencyEvaluationTemplate;
use App\Models\User;
use App\Support\PermanencyReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermanencyEvaluationController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && in_array(strtolower(trim((string) $request->user()->role)), ['admin', 'administrator'], true)
            && $request->user()->hasAdminPermission('employees'), 403);
    }

    private function authorizeHead(Request $request): User
    {
        $head = $request->user();
        abort_unless($head && strtolower(trim((string) $head->role)) === 'employee'
            && strtolower(trim((string) $head->status)) === 'approved'
            && strtolower(trim((string) $head->department_head)) === 'approved', 403);
        $head->loadMissing(['employee', 'applicant.position']);
        return $head;
    }

    public function customize(Request $request)
    {
        $this->authorizeAdmin($request);
        return view('Admin.permanencyEvaluation', [
            'template' => PermanencyEvaluationTemplate::latest('id')->first(),
            'reviews' => PermanencyEvaluation::with(['employeeUser', 'headUser', 'template'])->latest()->paginate(15),
        ]);
    }

    public function saveTemplate(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'notice_days' => ['required', 'integer', 'between:0,90'],
            'criteria' => ['required', 'array', 'min:1', 'max:20'],
            'criteria.*.label' => ['required', 'string', 'max:150', 'distinct'],
            'criteria.*.description' => ['nullable', 'string', 'max:500'],
        ]);
        $data['criteria'] = array_values($data['criteria']);
        $data['created_by'] = $request->user()->id;
        PermanencyEvaluationTemplate::create($data);
        return redirect()->route('admin.employeeEvaluation.customize')->with('success', 'Evaluation form published for department heads.');
    }

    public function index(Request $request, PermanencyReview $service)
    {
        $head = $this->authorizeHead($request);
        $template = PermanencyEvaluationTemplate::latest('id')->first();
        $employees = $template ? User::with(['employee', 'applicant.position'])
            ->whereRaw("LOWER(TRIM(role)) = 'employee'")
            ->whereRaw("LOWER(TRIM(status)) = 'approved'")->get()
            ->filter(fn ($employee) => $service->eligible($head, $employee, $template->notice_days))->values() : collect();
        $reviews = PermanencyEvaluation::with('template')->whereIn('employee_user_id', $employees->pluck('id'))->get();
        return view('employee.permanencyEvaluation', compact('template', 'employees', 'reviews', 'service'));
    }

    public function submit(Request $request, User $employee, PermanencyReview $service)
    {
        $head = $this->authorizeHead($request);
        $employee->loadMissing(['employee', 'applicant.position']);
        return DB::transaction(function () use ($request, $head, $employee, $service) {
            // Serialize submissions for the employee and preserve the published form used.
            User::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $template = PermanencyEvaluationTemplate::latest('id')->first();
            $teamReview = $request->routeIs('employee.employeeEvaluation.submit');
            $sameDepartment = $head->id !== $employee->id
                && strtolower(trim((string) $employee->role)) === 'employee'
                && strtolower(trim((string) $employee->status)) === 'approved'
                && $service->department($head) !== ''
                && strcasecmp($service->department($head), $service->department($employee)) === 0;
            abort_unless($template && ($teamReview ? $sameDepartment : $service->eligible($head, $employee, $template->notice_days)), 403);
            $due = $teamReview ? now()->toDateString() : $service->dueDate($employee)->toDateString();
            $rules = [
                'template_id' => ['required', Rule::in([$template->id])],
                'ratings' => ['required', 'array', 'size:'.count($template->criteria)],
                'recommendation' => ['required', Rule::in(['recommend_permanent', 'extend_probation', 'not_recommended'])],
                'comments' => ['nullable', 'string', 'max:5000'],
            ];
            foreach ($template->criteria as $index => $criterion) $rules['ratings.'.$index] = ['required', 'integer', 'between:1,5'];
            $data = $request->validate($rules, ['template_id.in' => 'HR updated the form. Refresh this page and use the latest criteria.']);
            if (PermanencyEvaluation::where('employee_user_id', $employee->id)->whereDate('due_date', $due)->exists()) {
                return back()->withErrors(['evaluation' => 'An evaluation has already been submitted for this review date.']);
            }
            PermanencyEvaluation::create([
                'employee_user_id' => $employee->id, 'head_user_id' => $head->id,
                'template_id' => $template->id, 'due_date' => $due,
                'ratings' => array_map(fn ($index) => (int) $data['ratings'][$index], array_keys($template->criteria)),
                'overall_rating' => round(array_sum($data['ratings']) / count($data['ratings']), 2),
                'recommendation' => $data['recommendation'], 'comments' => trim((string) ($data['comments'] ?? '')),
            ]);
            return redirect()->route($teamReview ? 'employee.employeeEvaluation' : 'employee.permanencyEvaluation')->with('success', 'Evaluation submitted to HR for review.');
        });
    }
}
