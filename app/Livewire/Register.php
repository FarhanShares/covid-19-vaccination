<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\VaccineCenter;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Registered;

class Register extends Component
{
    public int $nid;
    public string $dob;
    public string $name;
    public string $email;
    public int $vaccineCenter;
    public array $vaccineCenters;

    public function mount()
    {
        // For efficiency
        // TODO check if cache exists, separate this maybe, handle invalidation
        Cache::remember('vc_options', now()->addDay(), function () {
            return VaccineCenter::query()->select('id', 'name')->pluck('name', 'id')->toArray();
        });

        $this->vaccineCenters   = Cache::get('vc_options');
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        /**
         * The method 'getValidatedData' uses 'validate' method provided by Livewire,
         * which ensures that if validation fails, execution stops here.
         */
        $validatedData = $this->getValidatedData();

        /**
         * <<Notes by Farhan Israq>>
         *
         * Creating a very fast-paced high-traffic 'registration' process for apps like Covid-19
         * Vaccination Program can be streamlined with efficient database writes.
         *
         * We will follow the "Write-back cache" strategy where we store the data in cache storage first,
         * later create the user in asynchronous job (we'll run jobs in batch for even more performant updates).
         *
         * Write-back cache strategy has it's own limitations and implementation complexities.
         *
         * One of the added complexities is, if the user tries to re-register again, we must look-up
         * in two places for user existence check: in users table and in cache storage. This can be
         * easily resolved by using a repository.
         *
         * The other consideration is to have a persistent cache storage. This is out of the scope of
         * the task and more suitable for the DevOps team to see what suits better. E.g. setting up
         * regular backups for the cache storage, monitor any failures & recover it promptly when needed.
         *
         * The other option is to go for Write-through cache, but I don't want to go further on it.
         */
        // TODO use write-back cache strategy
        $user = User::create($validatedData);

        /**
         * Write-through cache
         *
         * Immediately cache user data and invalidate only when status is changed to scheduled
         * or vaccinated. The cache invalidation is handled by events.
         *
         * To make it fail-safe, a cron job is scheduled too.
         */
        Cache::rememberForever("user:{$user->nid}", fn() => $user);

        event(new Registered($user));

        // TODO add a flash message that registration is complete and user will be notified a day earlier
        $this->redirect(route('home', absolute: false), navigate: true);
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
     * Run validation and return with validated data.
     *
     * We could have also used a Request class, or even uses spatie/laravel-data class,
     * just keeping it simple here and, following the laravel volt's way of doing things.
     *
     * @return array
     */
    protected function getValidatedData(): array
    {
        // Here we may even add custom validation rules e.g. to check if dob and nid pair is valid
        $validated = $this->validate(
            rules: [
                'nid'   => ['required', 'integer', 'regex:/^\d{13}$|^\d{17}$/', 'unique:users,nid'],
                'dob'   => ['required', 'date'],
                'name'  => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
                'vaccineCenter' => ['required', 'integer', Rule::in($this->getVaccineCenterIds())],
            ],
            // Only adding the custom validation failed messages where necessary.
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
