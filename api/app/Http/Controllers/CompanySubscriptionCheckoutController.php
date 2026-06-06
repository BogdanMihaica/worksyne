<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class CompanySubscriptionCheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $company = $request->user()?->companyUser?->company;

        if (! $company) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $attributes = $request->validate([
            'subscription_plan_id' => ['required', 'integer', Rule::exists('subscription_plan', 'id')],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($attributes['subscription_plan_id']);
        $priceId = $this->stripePriceId($plan);

        if (! config('services.stripe.secret_key')) {
            return response()->json([
                'message' => 'Stripe is not configured.',
            ], 422);
        }

        if (! $priceId) {
            return response()->json([
                'message' => 'This plan is not available for Stripe checkout.',
            ], 422);
        }

        $order = Order::query()->create([
            'user_id' => $request->user()->id,
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        try {
            $session = $this->createStripeCheckoutSession($order, $plan, $priceId);
        } catch (RequestException $exception) {
            $order->update(['status' => 'failed']);

            return response()->json([
                'message' => $exception->response->json('error.message') ?? 'Unable to create Stripe checkout session.',
            ], 422);
        }

        $order->update(['external_id' => $session['id']]);

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $company = $request->user()?->companyUser?->company;

        if (! $company) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $attributes = $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        try {
            $session = $this->retrieveStripeCheckoutSession($attributes['session_id']);
        } catch (RequestException $exception) {
            return response()->json([
                'message' => $exception->response->json('error.message') ?? 'Unable to verify Stripe checkout session.',
            ], 422);
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return response()->json([
                'message' => 'Stripe checkout is not paid yet.',
            ], 422);
        }

        $order = Order::query()
            ->where('external_id', $session['id'])
            ->where('company_id', $company->id)
            ->firstOrFail();

        DB::transaction(function () use ($company, $order, $session) {
            Subscription::query()
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'canceled',
                    'ends_at' => now()->toDateString(),
                ]);

            $subscription = Subscription::query()->create([
                'company_id' => $company->id,
                'subscription_plan_id' => $order->subscription_plan_id,
                'external_id' => $session['subscription'] ?? null,
                'starts_at' => now()->toDateString(),
                'ends_at' => null,
                'status' => 'active',
            ]);

            $company->update(['subscription_plan_id' => $order->subscription_plan_id]);
            $order->update(['status' => 'paid']);

            Payment::query()->updateOrCreate(
                ['external_id' => $session['payment_intent'] ?? $session['id']],
                [
                    'order_id' => $order->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'status' => 'paid',
                    'paid_at' => now(),
                ],
            );
        });

        return response()->json([
            'message' => 'Subscription upgraded.',
        ]);
    }

    public function downgrade(Request $request): JsonResponse
    {
        $company = $request->user()?->companyUser?->company;

        if (! $company) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $freePlan = SubscriptionPlan::query()
            ->where('name', 'Free')
            ->where('price', 0)
            ->first();

        if (! $freePlan) {
            return response()->json([
                'message' => 'The Free plan is not available.',
            ], 422);
        }

        if ($company->subscription_plan_id === $freePlan->id) {
            return response()->json([
                'message' => 'Your company is already on the Free plan.',
            ]);
        }

        $activeSubscription = Subscription::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        try {
            $stripeSubscriptionId = $this->resolveStripeSubscriptionId($activeSubscription);

            if ($stripeSubscriptionId) {
                $this->cancelStripeSubscription($stripeSubscriptionId);
            }
        } catch (RequestException $exception) {
            return response()->json([
                'message' => $exception->response->json('error.message') ?? 'Unable to cancel the Stripe subscription.',
            ], 422);
        }

        DB::transaction(function () use ($activeSubscription, $company, $freePlan) {
            if ($activeSubscription) {
                $activeSubscription->update([
                    'status' => 'canceled',
                    'ends_at' => now()->toDateString(),
                ]);
            }

            Subscription::query()->create([
                'company_id' => $company->id,
                'subscription_plan_id' => $freePlan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => null,
                'status' => 'active',
            ]);

            $company->update(['subscription_plan_id' => $freePlan->id]);
        });

        return response()->json([
            'message' => 'Your company is now on the Free plan.',
        ]);
    }

    private function createStripeCheckoutSession(Order $order, SubscriptionPlan $plan, string $priceId): array
    {
        return Http::asForm()
            ->withToken(config('services.stripe.secret_key'))
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'subscription',
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
                'success_url' => config('services.stripe.success_url'),
                'cancel_url' => config('services.stripe.cancel_url'),
                'metadata[order_id]' => $order->id,
                'metadata[company_id]' => $order->company_id,
                'metadata[subscription_plan_id]' => $plan->id,
            ])
            ->throw()
            ->json();
    }

    private function retrieveStripeCheckoutSession(string $sessionId): array
    {
        return Http::withToken(config('services.stripe.secret_key'))
            ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId)
            ->throw()
            ->json();
    }

    private function resolveStripeSubscriptionId(?Subscription $subscription): ?string
    {
        if (! $subscription) {
            return null;
        }

        if ($subscription->external_id) {
            return $subscription->external_id;
        }

        $checkoutSessionId = Payment::query()
            ->where('subscription_id', $subscription->id)
            ->with('order:id,external_id')
            ->first()
            ?->order
            ?->external_id;

        if (! $checkoutSessionId) {
            return null;
        }

        if (! config('services.stripe.secret_key')) {
            abort(422, 'Stripe is not configured.');
        }

        $session = $this->retrieveStripeCheckoutSession($checkoutSessionId);

        return $session['subscription'] ?? null;
    }

    private function cancelStripeSubscription(string $subscriptionId): void
    {
        if (! config('services.stripe.secret_key')) {
            abort(422, 'Stripe is not configured.');
        }

        Http::asForm()
            ->withToken(config('services.stripe.secret_key'))
            ->delete('https://api.stripe.com/v1/subscriptions/'.$subscriptionId)
            ->throw();
    }

    private function stripePriceId(SubscriptionPlan $plan): ?string
    {
        return match (strtolower($plan->name)) {
            'pro' => config('services.stripe.pro_price_id'),
            'enterprise' => config('services.stripe.enterprise_price_id'),
            default => null,
        };
    }
}
