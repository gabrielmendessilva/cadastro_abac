<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'users' => User::count(),
            'clients' => Client::count(),
            'documents' => Document::count(),
            'active_clients' => Client::where('status', true)->count(),
        ];

        $latestClients = Client::latest()->take(10)->get();
        $latestDocuments = Document::with('client')->latest()->take(10)->get();

        return view('dashboard.index', compact('stats', 'latestClients', 'latestDocuments'));
    }
}
