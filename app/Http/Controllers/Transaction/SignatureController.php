<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\S3Helper;
use Inertia\Inertia;
use Inertia\Response;

class SignatureController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();

        $bills = Bill::with(['room.roomCategory.kost', 'tenant'])
            ->where('status', 'paid')
            ->whereNull('signature')
            ->whereDate('start_date', '<=', $today)
            ->latest()
            ->paginate(15)
            ->through(fn (Bill $bill) => [
                'id' => $bill->id,
                'room_number' => $bill->room->room_number ?? '-',
                'tenant_name' => $bill->tenant->name ?? '-',
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
            ]);

        return Inertia::render('Transactions/Signature/Index', [
            'bills' => $bills,
        ]);
    }

    public function sign(Request $request, Bill $bill): RedirectResponse
    {
        // validate incoming signature image (data URL)
        $validated = $request->validate([
            'signature' => 'required|string',
        ]);

        // only allow signing for paid bills without signature and with start_date <= today
        if ($bill->status !== 'paid') {
            return back()->with('error', 'Bill harus berstatus paid untuk tanda tangan.');
        }

        if ($bill->signature !== null) {
            return back()->with('error', 'Bill sudah memiliki signature.');
        }

        if ($bill->start_date && $bill->start_date->isAfter(Carbon::today())) {
            return back()->with('error', 'Bill belum dimulai, tidak dapat ditandatangani.');
        }

        // store signature image using S3Helper: write temp file, upload, then remove temp
        $data = $validated['signature'];
        $base64 = $data;
        $ext = 'png';
        if (Str::startsWith($data, 'data:')) {
            [$meta, $body] = explode(',', $data, 2) + [1 => ''];
            $base64 = $body;
            if (preg_match('/data:(image\/\w+);base64/', $meta, $m)) {
                $mime = $m[1];
                if ($mime === 'image/jpeg') $ext = 'jpg';
                elseif ($mime === 'image/png') $ext = 'png';
                else $ext = 'png';
            }
        }

        $decoded = base64_decode($base64);
        if ($decoded === false) {
            return back()->with('error', 'Data signature tidak valid.');
        }

        $tempFileName = (string) Str::uuid() . '.' . $ext;
        // save to local temp
        Storage::disk('local')->put('temp/' . $tempFileName, $decoded);

        try {
            // upload to supabase via helper
            $supabasePath = S3Helper::storeFileToS3('signatures', $tempFileName);
            $publicUrl = S3Helper::getUrlFileS3('signatures', $tempFileName);

            // remove local temp
            S3Helper::removeFileTemp($tempFileName);

            // save public URL in bill.signature
            $bill->update(['signature' => $publicUrl]);

            return back()->with('success', 'Signature berhasil ditambahkan.');
        } catch (\Exception $e) {
            // cleanup and return error
            S3Helper::removeFileTemp($tempFileName);
            return back()->with('error', 'Upload signature gagal: ' . $e->getMessage());
        }
    }
}
