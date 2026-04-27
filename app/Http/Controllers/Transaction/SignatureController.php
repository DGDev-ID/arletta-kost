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
            ->whereIn('status', ['paid', 'down_payment', 'finished_payment'])
            ->whereNull('signature')
            ->whereDate('start_date', '<=', $today)
            ->latest()
            ->paginate(15)
            ->through(fn(Bill $bill) => [
                'id' => $bill->id,
                'room_number' => $bill->room->room_number ?? '-',
                'tenant_name' => $bill->tenant->name ?? '-',
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
            ]);

        // Bills that have been signed
        $signedBills = Bill::with(['room.roomCategory.kost', 'tenant'])
            ->whereIn('status', ['paid', 'down_payment', 'finished_payment'])
            ->whereNotNull('signature')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn(Bill $bill) => [
                'id' => $bill->id,
                'room_number' => $bill->room->room_number ?? '-',
                'tenant_name' => $bill->tenant->name ?? '-',
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
                'signature' => $bill->signature,
            ]);

        return Inertia::render('Transactions/Signature/Index', [
            'bills' => $bills,
            'signedBills' => $signedBills,
        ]);
    }

    public function sign(Request $request, Bill $bill): RedirectResponse
    {
        // validate incoming signature image (data URL)
        $validated = $request->validate([
            'signature' => 'required|string',
        ]);

        // only allow signing for paid bills without signature and with start_date <= today
        if (!in_array($bill->status, ['paid', 'down_payment', 'finished_payment'])) {
            return back()->with('error', 'Bill harus berstatus paid/down_payment untuk tanda tangan.');
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
        Storage::disk('local')->put('temp/' . $tempFileName, $decoded);

        try {

            S3Helper::storeFileToS3('signatures', $tempFileName);
            $imgUrl = S3Helper::getUrlFileS3('signatures', $tempFileName);
            S3Helper::removeFileTemp($tempFileName);

            $bill->update(['signature' => $imgUrl]);

            return back()->with('success', 'Signature berhasil ditambahkan.');
        } catch (\Exception $e) {
            S3Helper::removeFileTemp($tempFileName);
            return back()->with('error', 'Upload signature gagal: ' . $e->getMessage());
        }
    }
}
