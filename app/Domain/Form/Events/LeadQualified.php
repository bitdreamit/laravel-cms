<?php

namespace App\Domain\Form\Events;

use App\Models\Tenant\FormSubmission;
use Illuminate\Foundation\Events\Dispatchable;

class LeadQualified
{
    use Dispatchable;

    public function __construct(public FormSubmission $submission) {}
}
