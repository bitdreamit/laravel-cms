<?php

namespace App\Domain\Content\Events;

use App\Models\Tenant\Form;
use App\Models\Tenant\FormSubmission;
use Illuminate\Foundation\Events\Dispatchable;

class FormSubmitted
{
    use Dispatchable;

    public function __construct(
        public FormSubmission $submission,
        public Form $form,
    ) {}
}
