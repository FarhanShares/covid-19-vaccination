<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {}

    /**
     * The search form page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('search');
    }

    /**
     * The search result page.
     *
     * We could have used a Request Class but this is really a simple one-input form,
     * which would be too much for this use case.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Contracts\View\View
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'nid_search'            => 'required|integer|min_digits:13|max_digits:17',
        ], [
            'nid_search.required'   => 'The NID field is required.',
            'nid_search.integer'    => 'The NID field should contain only digits.',
            'nid_search.min_digits' => 'The nid search field must have at least 13 digits.',
            'nid_search.max_digits' => 'The nid search field must not have more than 17 digits.',
        ]);

        $nid    = (int) $validated['nid_search'];
        $user   = $this->userRepository->findByNid($nid);
        $status = $user ? $user->status : VaccinationStatus::NOT_REGISTERED;

        if ($status === VaccinationStatus::SCHEDULED) {
            $meta        = $this->userRepository->withMetadata($user);
            $centerName  = $meta->vaccineCenter->name;
            $scheduledAt = $meta->vaccineAppointment->date->format('d M, Y');
        } else {
            $centerName  = null;
            $scheduledAt = null;
        }

        $linkHref        = route('search');
        $linkTitle       = 'Search Again? Click here.';

        if ($status === VaccinationStatus::NOT_REGISTERED) {
            $linkHref    = route('search');
            $linkTitle   = 'Register to get vaccinated from a nearby location.';
        }

        return view('search-result')
            ->with([
                'nid' => $nid,
                'user' => $user,
                'status' => $status,
                'linkHref' => $linkHref,
                'linkTitle' => $linkTitle,
                'centerName' => $centerName,
                'scheduledAt' => $scheduledAt,
            ]);
    }
}
