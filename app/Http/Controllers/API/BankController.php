<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Bankdeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $query = Bankdeposit::query();

        $length = $request->length ?: 10;
        $page = $request->page ?: 1;
        $offset = ($page - 1) * $length;

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('bank_name', 'like', '%' . $search . '%')
                ->orWhere('account_name', 'like', '%' . $search . '%');
        }

        $bankDepositList = $query->offset($offset)->limit($length)->get();

        $total = Bankdeposit::count();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $bankDepositList,
        ]);
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'type' => 'required|in:status_bank,show_bank,show_form',
            'status' => 'required|in:active,inactive,maintenance,offline,showQris,showAccNo',
        ]);

        $bankDeposit = Bankdeposit::find($id);

        if (!$bankDeposit) {
            return response()->json(['error' => 'Bank deposit not found'], 404);
        }

        if ($request->type === 'status_bank') {
            $bankDeposit->status_bank = $request->status;
        } elseif ($request->type === 'show_bank') {
            $bankDeposit->show_bank = $request->status;
        } elseif ($request->type === 'show_form') {
            $bankDeposit->show_form = $request->status;
        }

        $bankDeposit->save();

        return response()->json([
            'message' => 'Bank deposit status updated successfully',
            'show_bank' => $bankDeposit->show_bank,
            'status_bank' => $bankDeposit->status_bank,
            'show_form' => $bankDeposit->show_form,
        ]);
    }

    public function createBank(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
            'bankName' => 'required|string',
            'accountName' => 'required|string',
            'accountNumber' => 'required|string',
            'minTransaction' => 'required|numeric',
            'maxTransaction' => 'required|numeric',
            'qrisImage' => 'nullable|string',
            'uniqueCode' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = $request->category;
        $bankName = $request->bankName;
        $accountName = $request->accountName;
        $accountNumber = $request->accountNumber;
        $minTransaction = $request->minTransaction;
        $maxTransaction = $request->maxTransaction;
        $uniqueCode = $request->unique_code;

        $qrisImageBase64 = $request->qrisImage;

        $bankDeposit = new Bankdeposit();
        $bankDeposit->type = $category;
        $bankDeposit->bank_name = $bankName;
        $bankDeposit->account_name = $accountName;
        $bankDeposit->account_number = $accountNumber;
        $bankDeposit->min_deposit = $minTransaction;
        $bankDeposit->max_deposit = $maxTransaction;
        $bankDeposit->qris_img = $qrisImageBase64;
        $bankDeposit->unique_code = $uniqueCode;
        $bankDeposit->save();

        $bankDeposit = $bankDeposit->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Bank Deposit created successfully!',
            'data' => $bankDeposit,
        ], 201);
    }


    public function destroy(string $id)
    {
        try {
            $bankDeposit = Bankdeposit::findOrFail($id);

            $bankDeposit->delete();

            return response()->json([
                'message' => 'Bank deposit successfully deleted.',
                'data' => null
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Bank deposit not found.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the bank deposit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'accountName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:20',
            'minTransaction' => 'required|numeric|min:0',
            'maxTransaction' => 'required|numeric|min:0|gte:minTransaction',
            'qrisImage' => 'nullable|string',
            'uniqueCode' => 'nullable|string|max:50',
        ]);

        try {
            $bankDeposit = Bankdeposit::findOrFail($id);

            $bankDeposit->account_name = $validatedData['accountName'];
            $bankDeposit->account_number = $validatedData['accountNumber'];
            $bankDeposit->min_deposit = $validatedData['minTransaction'];
            $bankDeposit->max_deposit = $validatedData['maxTransaction'];
            $bankDeposit->qris_img = $validatedData['qrisImage'];
            $bankDeposit->unique_code = $validatedData['uniqueCode'];

            $bankDeposit->save();

            return response()->json([
                'message' => 'Bank deposit updated successfully',
                'data' => $bankDeposit,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to update bank deposit',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
