<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user || ($user->rol ?? '') !== 'admin') {
            abort(403);
        }

        $logs = Log::orderByDesc('id')->paginate(25);

        return view('logs.index', [
            'logs' => $logs,
            'currentUser' => $user,
            'isAdmin' => true,
        ]);
    }
}
