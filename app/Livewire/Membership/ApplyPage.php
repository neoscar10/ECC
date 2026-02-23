<?php

namespace App\Livewire\Membership;

use Livewire\Component;

class ApplyPage extends Component
{
    public function render()
    {
        return <<<'HTML'
            <div class="p-5 text-center">
                <h1>Membership Application</h1>
                <p class="lead">The application form is under maintenance. Please check back soon.</p>
                <a href="/" class="btn btn-primary">Back to Home</a>
            </div>
        HTML;
    }
}
