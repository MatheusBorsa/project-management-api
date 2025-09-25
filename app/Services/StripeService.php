<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use App\Models\User;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCustomer(User $user)
    {
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => $user->id]
        ]);

        return $customer;
    }

    public function createCheckoutSession(User $user, string $priceId)
    {
        $session = Session::create([
            'customer_email' => $user->email,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => url('/billing/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/billing/cancel'),
            'subscription_data' => [
                'metadata' => ['user_id' => $user->id],
            ],
        ]);

        return $session;
    }

    public function createBillingPortalSession(string $customerId)
    {
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customerId,
            'return_url' => url('/billing')
        ]);

        return $session;
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $subscription = Subscription::retrieve($subscriptionId);
        return $subscription->cancel();
    }
}
