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
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\URL;

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
        // For efficiency
        // TODO check if cache exists, separate this maybe, handle invalidation
        Cache::remember('vc_options', now()->addDay(), function () {
            return VaccineCenter::query()->select('id', 'name')->pluck('name', 'id')->toArray();
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
                'nid'   => ['required', 'integer', 'regex:/^\d{13}$|^\d{17}$/', 'unique:users,nid'],
                'dob'   => ['required', 'date'],
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
