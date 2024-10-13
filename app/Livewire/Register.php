<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\VaccineCenter;
use App\Repositories\UserRepository;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;

class Register extends Component
{
    public int $nid;
    public string $dob;
    public string $name;
    public string $email;
    public int $vaccineCenter;
    public array $vaccineCenters;

    public function mount(): void
    {
        /**
         * For efficiency, we are caching the data for longer period of times assuming that
         * vaccine centers are not occasionally added or updated.
         *
         * The cache should be invalidated when create or update event occurs.
         * Doable, but skipping it for now.
         */
        Cache::remember('vc_options', now()->addDays(10), function () {
            return VaccineCenter::query()
                ->select('id', 'name')
                ->pluck('name', 'id')
                ->toArray();
        });

        $this->vaccineCenters = Cache::get('vc_options');
    }

    /**
     * <<Notes by Farhan Israq>>
     *
     * Handle an incoming registration request efficiently.
     *
     * For a COVID-19 vaccination program app, the traffic pattern would typically be write-heavy
     * at the beginning (due to user registrations) and then gradually shift towards being read-heavy
     * (for users checking their vaccination status or appointment details).
     *
     * Creating a very fast-paced high-traffic 'registration' process for apps like Covid-19
     * Vaccination Program can be streamlined with efficient database writes.
     *
     * We will implement the "Write-back cache" strategy where we store the data in cache storage first,
     * later create the user in asynchronous job (we'll run jobs in batch for even more performant updates).
     *
     * One more reason of choosing "Write-back strategy" is because we have to notify the user at 9 PM,
     * which means we certainly have time to do the actual registration process asynchronously.
     *
     * Write-back cache strategy has it's own limitations and implementation complexities.
     *
     * One of the added complexities is, if the user tries to re-register again, we must look-up
     * in two places for user existence check: in users table and in cache storage. However, This
     * can be easily resolved by implementing repository pattern.
     *
     * The other consideration is to have a persistent cache storage. This is out of the scope of
     * the task and more suitable for the DevOps team to see what suits better. E.g. setting up
     * regular backups for the cache storage, monitor any failures & recover it promptly when needed.
     *
     * The other option is to go for Write-through cache, but I don't want to go further on it.
     *
     * But this brings the question, why didn't I just simply dispatched a job? Good catch. The simple
     * answer to that is to prevent the user from re-registering or give him immediate result upon checking
     * his appointment (scheduled) date.
     *
     * We never know with a very high traffic, there might be a long running process and the user data
     * is yet to be stored after a long delay! We'll eventually get some curious users who try to
     * re-submit registration form or, he wants to check his appointment status (scheduled data) just
     * right after registering himself! Yes, I'm solving that issue with Write-back caching strategy.
     */
    public function register(): void
    {
        /**
         * The method 'getValidatedData' uses 'validate' method provided by Livewire,
         * which ensures that if validation fails, execution stops here.
         */
        $validatedData = $this->getValidatedData();

        // Create a model instance form the validated data
        $user = new User($validatedData);

        // The user repository handles most of the Write-back strategy
        (new UserRepository())->save($user);

        // Redirect the user with a registration completed notification
        session()->flash(UserRepository::REGISTRATION_COMPLETED_SESSION, $user->nid);
        $this->redirect(route(name: 'success', absolute: false), navigate: true);
    }

    public function render()
    {
        // todo: add auto-fill button
        return view('livewire.register');
    }

    /**
     * This is to improve the performance of validation rules for checking if vaccine center is valid.
     * We are trying to avoid DB calls as much as possible for improved efficiency.
     *
     * @return array
     */
    #[Computed]
    protected function getVaccineCenterIds(): array
    {
        return collect($this->vaccineCenters)->keys()->toArray() ?? [];
    }

    /**
     * <<Notes by Farhan Israq>>
     *
     * Run validation and return with validated data.
     *
     * We could have also used a Controller / Request class, or even uses spatie/laravel-data class,
     * just keeping things simple here.
     *
     * We could have even added custom validation rules e.g. to check if 'dob and nid pair' is valid,
     * again, for the sake of simplicity (and, also not the scope of the current project), skipping it.
     *
     * @return array
     */
    protected function getValidatedData(): array
    {
        $validated = $this->validate(
            rules: [
                'nid'   => [
                    'required',
                    'integer',
                    'regex:/^\d{13}$|^\d{17}$/',
                    function ($attribute, $value, $fail) {
                        if ((new UserRepository())->exists($value)) {
                            $fail('The NID has already been registered.');
                        }
                    },
                ],
                'dob'   => ['required', 'date'], // purposefully skipped other potential validations
                'name'  => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
                'vaccineCenter' => ['required', 'integer', Rule::in($this->getVaccineCenterIds())],
            ],
            // Only adding the custom messages where necessary.
            messages: [
                'nid.required' => 'The NID field is required.',
                'nid.regex'    => 'The NID must be either a valid 13 or 17-digit number.',
                'nid.unique'   => 'This NID is already registered.',
                'email.unique' => 'This email is already in use.',
                'vaccineCenter.in' => 'The selected vaccine center does not exist.',
            ],
        );

        // Adding additional necessary data
        $validated['status'] = VaccinationStatus::NOT_SCHEDULED->value;
        $validated['vaccine_center_id'] = $validated['vaccineCenter'];

        return $validated;
    }
}
