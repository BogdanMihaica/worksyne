<?php

namespace App\Http\Controllers;

use App\Models\Workstream;
use Illuminate\Database\Eloquent\Model;

class WorkstreamController extends ApiResourceController
{
    protected string $modelClass = Workstream::class;

    protected function storeRules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
