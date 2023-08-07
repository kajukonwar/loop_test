<?php
namespace App\Http\Services;
use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class SuperPaymentProvider implements PaymentProviderInterface
{

    public function pay($order_id, $email, $amount)
    {
        $data = [
            'order_id' => $order_id,
            'customer_email' => $email,
            'value' => $amount
        ];

        try {
            $response = Http::post(config('loop.payment.providers.super_payment_provider.url'), $data);
            if (!$response->successful()) {
                throw new \Exception('Payment request was not successful');
            } 
            $payment = $response->json();
            Log::info('Payment request call success', ['order_id' => $order_id, 'response' => $payment]);

            if (empty($payment['message']) || $payment['message'] != "Payment Successful") {
                Log::error('Payment failed', ['order_id' => $order_id, 'response' => $payment]);
                return false;
            }
            Log::info('Payment success', ['order_id' => $order_id, 'response' => $payment]);
            return true;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Payment error', ['order_id' => $order_id, 'error' => $e->getMessage()]);
            
        } catch (\Exception $e) {
            // Handle other exceptions (e.g., network errors)
            Log::error('Payment error', ['order_id' => $order_id, 'error' => $e->getMessage()]);
        }
        return false;
    }
}