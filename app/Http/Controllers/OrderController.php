<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();
        return response()->json(new OrderCollection($orders));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_email' => 'required',
            'product_id' => 'required',
        ]);
        
        $validated = $validator->validated();
        $customer = Customer::where('email', $validated['user_email'])->first();
        $product = Product::find($validated['product_id']);
        $validator->after(function ($validator) use($customer, $product) {
            if (empty($customer)) {
                $validator->errors()->add(
                    'user_email', 'Customer does not exist'
                );
            }

            if (empty($product)) {
                $validator->errors()->add(
                    'product_id', 'Product does not exist'
                );
            }
        });
         
        if ($validator->fails()) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = new Order;
        $order->customer_id = $customer->id;
        $order->product_id = $product->id;
        $order->is_paid = 0;
        $order->save();
        return response()->json(new OrderResource($order));
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        return response()->json(new OrderResource($order));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|integer',
            'is_paid' => [
                'nullable',
                Rule::in([0, 1]),
            ]
        ]);
        $validator->stopOnFirstFailure();
        $validated = $validator->validated();
        $order = Order::find($id);
        if (isset($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            $validator->after(function ($validator) use($order, $product) {
                if ($order->is_paid) {
                    $validator->errors()->add(
                        'order_id', 'Unable to update. Order is already marked as paid'
                    );
                    throw new ValidationException($validator);
                }

                if (empty($product)) {
                    $validator->errors()->add(
                        'product_id', 'Product does not exist'
                    );
                    throw new ValidationException($validator);
                }

                if (Order::where('product_id', $product->id)->where('id', $order->id)->exists()) {
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

        foreach (['is_paid', 'product_id'] as $field) {
            if (isset($validated[$field])) {
                $order->$field = $validated[$field];
            }
        }
        $order->save();
        return response()->json(new OrderResource($order));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}