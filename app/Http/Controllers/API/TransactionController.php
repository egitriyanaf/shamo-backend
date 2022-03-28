<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit');
        $status = $request->input('status');

        if($id){
            $transactions = Transaction::with(['items.product'])->find($id);

            if($transactions){
                return ResponseFormatter::success($transactions, 'Transaction found.');
            }else{
                return ResponseFormatter::error(null, 'Transaction not found.', 404);
            }
        }else{
            return ResponseFormatter::error(null, 'Transaction not found.', 404);
        }

        $transactions = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if($status){
            $transactions = $transactions->where('status', $status);
        }

        return ResponseFormatter::success($transactions->paginate($limit), 'Transactions found.');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'total_price' => 'required|numeric',
            'shipping_price' => 'required|numeric',
            'status' => 'required|in:pending,paid,shipped,delivered,cancelled',
        ]);

        $transactions = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->input('address'),
            'total_price' => $request->input('total_price'),
            'shipping_price' => $request->input('shipping_price'),
            'status' => $request->input('status'),
        ]);

        foreach($request->items as $product){
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'transactions_id' => $transactions->id,
                'products_id' => $product['id'],
                'quantity' => $product['quantity'] ,
            ]);
        }

        return ResponseFormatter::success($transactions->load('items.product'), 'Transaction created.');
    }
}
