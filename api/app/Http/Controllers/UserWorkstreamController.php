<?php

namespace App\Http\Controllers;

use App\Models\UserWorkstream;
use Illuminate\Database\Eloquent\Model;

class UserWorkstreamController extends ApiResourceController
{
    protected string $modelClass = UserWorkstream::class;

    protected function storeRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'workstream_id' => ['required', 'integer', 'exists:workstream,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'workstream_id' => ['sometimes', 'required', 'integer', 'exists:workstream,id'],
        ];
    }
}
