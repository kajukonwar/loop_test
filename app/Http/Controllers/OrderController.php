<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Contracts\PaymentProviderInterface;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('products')->get();
        return response()->json(new OrderCollection($orders));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_email' => 'required',
        ]);
        
        $validated = $validator->validated();

        if (!empty($validated['user_email'])) {
            $customer = Customer::where('email', $validated['user_email'])->first();
            $validator->after(function ($validator) use($customer) {
                if (empty($customer)) {
                    $validator->errors()->add(
                        'user_email', 'Customer does not exist'
                    );
                }
            });
        }

        if ($validator->fails()) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = new Order;
        $order->customer_id = $customer->id;
        $order->is_paid = 0;
        $order->save();
        return response()->json(new OrderResource($order));
    }

    public function show($id)
    {
        $order = Order::with('products')->find($id);
        if (empty($order)) {
            return response()->json(['order does not exist'], 404);
        }
        return response()->json(new OrderResource($order));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|integer',
            'is_paid' => [
                'nullable',
                Rule::in([0, 1]),
            ]
        ]);
        $validator->stopOnFirstFailure();
        $validated = $validator->validated();
        if (empty($validated)) {
            return response()->json('Nothing to update! Please send payload. Accepted params are customer_id and is_paid');
        }
        $order = Order::with('products')->find($id);
        if (empty($order)) {
            $validator->errors()->add(
                'id', 'Order does not exist'
            );
            throw new ValidationException($validator);
        }

        if ($order->is_paid) {
            $validator->errors()->add(
                'id', 'Unable to update. Order is already marked as paid'
            );
            throw new ValidationException($validator);
        }

        if (isset($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validator->after(function ($validator) use($order, $customer) {
                if (empty($customer)) {
                    $validator->errors()->add(
                        'customer_id', 'Customer does not exist'
                    );
                    throw new ValidationException($validator);
                }
            });
        }

        if ($validator->fails()) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach (['is_paid', 'customer_id'] as $field) {
            if (isset($validated[$field])) {
                $order->$field = $validated[$field];
            }
        }
        $order->save();
        $order->refresh();
        return response()->json(new OrderResource($order));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->products()->detach();
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function addNewProduct(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
        ]);
        
        $validated = $validator->validated();
        $order = Order::with('products')->find($id);

        if (!empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            $validator->after(function ($validator) use($order, $product) {
                if (empty($order)) {
                    $validator->errors()->add(
                        'id', 'Order does not exist'
                    );
                    throw new ValidationException($validator);
                }
    
                if ($order->is_paid) {
                    $validator->errors()->add(
                        'id', 'Unable to add new product to order. Order is already marked as paid'
                    );
                    throw new ValidationException($validator);
                }
    
                if (empty($product)) {
                    $validator->errors()->add(
                        'product_id', 'Product does not exist'
                    );
                    throw new ValidationException($validator);
                }
    
                if (OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)->exists()) {
                    $validator->errors()->add(
                        'product_id', 'This Product already exists in the order'
                    );
                    throw new ValidationException($validator);
                }
            });
        }
    
        if ($validator->fails()) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order->products()->attach($product, ['amount' => $product->price]);
        $order->amount = OrderProduct::where('order_id', $order->id)->sum('amount');
        $order->save();
        
        $order->refresh();
        return response()->json(new OrderResource($order));
    }

    public function makePayment(Request $request, PaymentProviderInterface $super_pp, $id)
    {
        $order = Order::with('customer')->find($id);
        if (empty($order)) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => 'Order does not exist',
            ], 422);
        }

        if ($super_pp->pay($order->id, $order->customer->email, $order->amount)) {
            $order->is_paid = 1;
            $order->save();
            return response()->json(['status' => 'success', 'message' => 'Payment success']);
        }
        return response()->json(['status' => 'error', 'message' => 'Payment failed']);
    }
}