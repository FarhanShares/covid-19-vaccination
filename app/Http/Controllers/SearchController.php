<?php

namespace App\Http\Controllers;

use App\Support\Enums\VaccinationStatus;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $status = VaccinationStatus::VACCINATED;

        return view('search-result')
            ->with('status', $status);
    }
}
