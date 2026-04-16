<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InquiryApiController extends ApiBaseController
{
    /**
     * POST /api/inquiries
     * Submit a contact inquiry from the landing page.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'kost_id' => 'nullable|exists:m_kosts,id',
            'category_id' => 'nullable|exists:room_categories,id',
            'message' => 'required|string|max:2000',
            'check_in_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->clientError('Validation failed.', $validator->errors(), 422);
        }

        try {
            $inquiry = DB::table('inquiries')->insertGetId([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'kost_id' => $request->input('kost_id'),
                'category_id' => $request->input('category_id'),
                'message' => $request->input('message'),
                'check_in_date' => $request->input('check_in_date'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->success(
                ['id' => $inquiry],
                'Inquiry berhasil dikirim. Admin akan menghubungi Anda segera.',
                201
            );
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }
}
