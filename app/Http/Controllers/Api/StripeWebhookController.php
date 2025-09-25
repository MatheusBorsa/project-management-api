<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook: ' . $event->type);

        switch ($event->type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdate($event->data->object);
                break;
                
            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancel($event->data->object);
                break;
                
            case 'invoice.payment_succeeded':
                $this->handlePaymentSuccess($event->data->object);
                break;
                
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionUpdate($subscription)
    {
        $user = User::where('stripe_id', $subscription->customer)->first();
        
        if (!$user) {
            Log::error('User not found for customer: ' . $subscription->customer);
            return;
        }

        Subscription::updateOrCreate(
            ['stripe_id' => $subscription->id],
            [
                'user_id' => $user->id,
                'stripe_status' => $subscription->status,
                'stripe_price' => $subscription->items->data[0]->price->id,
                'trial_ends_at' => $subscription->trial_end ? 
                    \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
                'ends_at' => $subscription->ended_at ? 
                    \Carbon\Carbon::createFromTimestamp($subscription->ended_at) : null,
            ]
        );
    }

    protected function handleSubscriptionCancel($subscription)
    {
        $subscription = Subscription::where('stripe_id', $subscription->id)->first();
        
        if ($subscription) {
            $subscription->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
        }
    }

    protected function handlePaymentSuccess($invoice)
    {
        if ($invoice->subscription) {
            $subscription = Subscription::where('stripe_id', $invoice->subscription)->first();
            if ($subscription) {
                $subscription->update(['stripe_status' => 'active']);
            }
        }
    }

    protected function handlePaymentFailed($invoice)
    {
        if ($invoice->subscription) {
            $subscription = Subscription::where('stripe_id', $invoice->subscription)->first();
            if ($subscription) {
                $subscription->update(['stripe_status' => 'past_due']);
            }
        }
    }
}