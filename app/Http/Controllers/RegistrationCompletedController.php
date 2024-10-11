<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepository;

class RegistrationCompletedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, UserRepository $userRepository)
    {
        $nid = $request->session()->get(UserRepository::REGISTRATION_COMPLETED_SESSION);

        if (!$nid) {
            return to_route('home')->with("error", "Invalid Request!");
        }

        $user = $userRepository->findByNid($nid);

        return view('success', ['user' => $user, 'nid' => $nid]);
    }
}
