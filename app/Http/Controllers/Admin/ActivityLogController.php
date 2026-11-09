<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        // Checked against the role itself: the logs cover every admin,
        // including other Super Admins, so no delegated permission opens them.
        abort_unless($request->user()->hasRole('Super Admin'), 403);

        $category = $request->input('kategori') === ActivityLog::CATEGORY_ALUMNI
            ? ActivityLog::CATEGORY_ALUMNI
            : ActivityLog::CATEGORY_ADMIN;

        $displayTz = config('app.display_timezone');

        $logs = ActivityLog::query()
            ->where('category', $category)
            ->with(['impersonator:id,name', 'alumni:id,nim,nama'])
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.mb_strtolower($request->string('q')->trim()->value()).'%';
                $query->where(fn (Builder $q) => $q
                    ->whereRaw('LOWER(actor_name) LIKE ?', [$term])
                    ->orWhereHas('user', fn (Builder $u) => $u->whereRaw('LOWER(email) LIKE ?', [$term])->orWhereRaw('LOWER(nim) LIKE ?', [$term]))
                    ->orWhereHas('alumni', fn (Builder $a) => $a->whereRaw('LOWER(nim) LIKE ?', [$term])));
            })
            ->when($request->filled('aktivitas'), fn (Builder $q) => $q->where('description', $request->string('aktivitas')->value()))
            ->when($request->input('status') === 'gagal', fn (Builder $q) => $q->where('succeeded', false))
            // The dates picked are WIB days; created_at is stored in UTC.
            ->when($request->filled('dari'), fn (Builder $q) => $q->where(
                'created_at', '>=', $request->date('dari', null, $displayTz)->startOfDay()->utc(),
            ))
            ->when($request->filled('sampai'), fn (Builder $q) => $q->where(
                'created_at', '<=', $request->date('sampai', null, $displayTz)->endOfDay()->utc(),
            ))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'category' => $category,
            'activities' => ActivityLog::where('category', $category)->distinct()->orderBy('description')->pluck('description'),
        ]);
    }
}
