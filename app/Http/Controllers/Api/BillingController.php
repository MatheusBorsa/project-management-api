<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session;

class BillingController extends Controller
{
    protected $stripe;

    public function __construct(StripeService $stripe)
    {
        $this->stripe = $stripe;
    }

    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user->stripe_id) {
            $customer = $this->stripe->createCustomer($user);
            $user->stripe_id = $customer->id;
            $user->save();
        }

        $sessionData = [
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $request->price_id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => url('/billing/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/billing/cancel'),
            'subscription_data' => [
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ],
        ];

        $session = Session::create($sessionData);

        return $session;
    }

    public function subscriptionStatus()
    {
        $user = Auth::user();

        return response()->json([
            'subscribed' => $user->subscribed(),
            'on_trial' => $user->onTrial(),
            'subscription' => $user->subscription,
        ]);
    }

    public function billingPortal()
    {
        $user = Auth::user();

        if (!$user->stripe_id) {
            return response()->json(['error' => 'No subscription found'], 400);
        }

        $session = $this->stripe->createBillingPortalSession($user->stripe_id);

        return response()->json([
            'url' => $session->url,
        ]);
    }

    public function cancelSubscription()
    {
        $user = Auth::user();

        if (!$user->subscription) {
            return response()->json(['error' => 'No active subscription'], 400);
        }

        $this->stripe->cancelSubscription($user->subscription->stripe_id);
        $user->subscription->update(['stripe_status' => 'canceled']);

        return response()->json(['message' => 'Subscription canceled']);
    }
}