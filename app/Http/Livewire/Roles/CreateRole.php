<?php

namespace App\Http\Livewire\Roles;

use App\Http\Livewire\MapsAbilitiesForRoleCreation;
use App\Tenant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Silber\Bouncer\Bouncer;

class CreateRole extends Component
{
    use AuthorizesRequests, MapsAbilitiesForRoleCreation;

    public $title;

    public $name;

    public $mappedAbilities;

    public function mount()
    {
        $this->mappedAbilities = $this->getMappedAbililites();
    }

    public function submit(Bouncer $bouncer)
    {
        $this->authorizeForUser(auth()->user(), 'create-role');

        $validRole = Validator::make(
            [
                'title' => $this->title,
                'name' => $this->name,
            ],
            [
                'title' => ['required', 'min:3', Tenant::uniqueRule('roles', 'title', 'scope')],
                'name' => ['required', 'min:3', Tenant::uniqueRule('roles', 'name', 'scope')],
            ]
        )->validate();

        $selectedAbilites = $this->getAllowedAbilitiesFrom($this->mappedAbilities);

        $abilities = collect($this->abilities)->filter(function ($ability) use ($selectedAbilites) {
            return in_array($ability->name, $selectedAbilites);
        });

        $bouncer->scope()->onceTo(tenant()->id, function () use ($bouncer, $abilities, $validRole) {
            /** @var \Silber\Bouncer\Database\Role */
            $role = $bouncer->role()->query()->create([
                'title' => $validRole['title'],
                'name' => $validRole['name'],
                'scope' => tenant()->id
            ]);

            $role->allow($abilities);
        });

        return redirect()->route('roles.index');
    }

    public function getAbilitiesProperty()
    {
        /** @var Bouncer  */
        $bouncer = app(Bouncer::class);

        return $bouncer->ability()->query()->where([
            'scope' => tenant()->id
        ])->get();
    }

    public function render()
    {
        return view('livewire.roles.create-role', [
            'abilities' => $this->abilities,
        ]);
    }

    protected function getConfig()
    {
        return config('abilities');
    }
}
