<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StatementService;
use Symfony\Component\HttpFoundation\Response;

class StatementController extends Controller
{
    public function pdf(User $user, StatementService $statements): Response
    {
        // A tenant may only download their own statement; admins may download anyone's.
        if (!auth()->user()->isAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized access to statement.');
        }

        return response($statements->pdf($user), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="statement_' . $user->id . '_' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }
}
