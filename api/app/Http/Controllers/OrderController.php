<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends ApiResourceController
{
    protected string $modelClass = Order::class;

    public function index(): JsonResponse
    {
        $user = request()->user();
        $companyId = $user?->companyUser?->company_id;

        if (! $user?->is_admin && ! $companyId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(
            QueryBuilder::for(
                Order::query()
                    ->with(['user', 'company', 'subscriptionPlan'])
                    ->when(! $user->is_admin, fn ($query) => $query->where('company_id', $companyId))
            )
                ->allowedFilters(
                    AllowedFilter::callback('company', function ($query, $value) {
                        $query->whereHas('company', function ($query) use ($value) {
                            $query->where('name', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::callback('user_email', function ($query, $value) {
                        $query->whereHas('user', function ($query) use ($value) {
                            $query->where('email', 'like', '%'.$value.'%');
                        });
                    }),
                    AllowedFilter::exact('user_id'),
                    AllowedFilter::exact('company_id'),
                    AllowedFilter::exact('subscription_plan_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('currency'),
                    'external_id',
                )
                ->allowedSorts('amount', 'status', 'created_at')
                ->paginate()
                ->appends(request()->query())
        );
    }

    protected function storeRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:user,id'],
            'company_id' => ['required', 'integer', 'exists:company,id'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plan,id'],
            'amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'paid', 'failed'])],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:user,id'],
            'company_id' => ['sometimes', 'required', 'integer', 'exists:company,id'],
            'subscription_plan_id' => ['sometimes', 'required', 'integer', 'exists:subscription_plan,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'paid', 'failed'])],
        ];
    }
}
