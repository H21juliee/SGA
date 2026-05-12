<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolYearOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $schoolYearId = $request->route('school_year_id')
            ?? $request->input('school_year_id');

        if ($schoolYearId) {
            $year = SchoolYear::findOrFail($schoolYearId);

            if ($year->is_closed) {
                if ($request->wantsJson() || $request->header('X-Inertia')) {
                    return back()->with('error', 'Este año escolar está cerrado. Los datos son inmutables.');
                }

                abort(403, 'Este año escolar está cerrado. Los datos son inmutables.');
            }
        }

        return $next($request);
    }
}
