<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\TermsCondition;
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

        $terms = TermsCondition::orderBy('order')->get()->map(fn($t) => [
            'id'      => $t->id,
            'order'   => $t->order,
            'content' => $t->content,
        ]);

        return Inertia::render('Transactions/Signature/Index', [
            'bills'       => $bills,
            'signedBills' => $signedBills,
            'terms'       => $terms,
        ]);
    }

    public function show(Bill $bill): Response
    {
        $bill->load(['tenant', 'room.roomCategory.kost', 'transactions.details']);

        $data = [
            'id'             => $bill->id,
            'total_price'    => (float) $bill->total_price,
            'dp_amount'      => $bill->dp_amount ? (float) $bill->dp_amount : null,
            'payment_scheme' => $bill->payment_scheme,
            'start_date'     => $bill->start_date?->format('d-m-Y'),
            'due_date'       => $bill->due_date?->format('d-m-Y'),
            'status'         => $bill->status,
            'signature'      => $bill->signature ? $this->resolveSignatureUrl($bill->signature) : null,
        ];

        $tenant = null;
        if ($bill->tenant) {
            $tenant = [
                'id'           => $bill->tenant->id,
                'name'         => $bill->tenant->name,
                'email'        => $bill->tenant->email,
                'phone_number' => $bill->tenant->phone_number,
                'nik'          => $bill->tenant->nik ?? '-',
                'address'      => $bill->tenant->address ?? '-',
            ];
        }

        $room = null;
        if ($bill->room) {
            $room = [
                'id'            => $bill->room->id,
                'room_number'   => $bill->room->room_number,
                'kost_name'     => $bill->room->roomCategory->kost->name ?? '-',
                'category_name' => $bill->room->roomCategory->name ?? '-',
            ];
        }

        $transactions = $bill->transactions->map(function ($t) {
            return [
                'id'               => $t->id,
                'order_id'         => $t->order_id,
                'payment_type'     => $t->payment_type,
                'transaction_type' => $t->transaction_type,
                'total_price'      => (float) $t->total_price,
                'status'           => $t->status,
                'created_at'       => $t->created_at->format('d-m-Y H:i'),
                'details'          => $t->details->map(fn($d) => [
                    'id'         => $d->id,
                    'status'     => $d->status,
                    'created_at' => $d->created_at->format('d-m-Y H:i'),
                ]),
            ];
        });

        return Inertia::render('Transactions/Signature/Show', [
            'bill'         => $data,
            'tenant'       => $tenant,
            'room'         => $room,
            'transactions' => $transactions,
        ]);
    }

    public function sign(Request $request, Bill $bill): RedirectResponse
    {
        // validate incoming signature image (data URL)
        $validated = $request->validate([
            'signature' => 'required|string',
        ]);

        // only allow signing for paid bills without signature and with start_date <= today
        if (!in_array($bill->status, ['paid', 'finished_payment'])) {
            return back()->with('error', 'Bill harus berstatus paid/finished_payment untuk tanda tangan.');
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

    /**
     * Extract the storage path from a full Supabase URL and return a signed URL.
     * If the value is not a URL (e.g. already a path), use it directly.
     */
    private function resolveSignatureUrl(string $signature): string
    {
        // If it's a full Supabase public URL, extract the path inside the bucket
        $bucketName = config('services.supabase.bucket');
        $marker     = "/object/public/{$bucketName}/";

        if ($bucketName && str_contains($signature, $marker)) {
            $storagePath = substr($signature, strpos($signature, $marker) + strlen($marker));
            return S3Helper::getSignedUrl($storagePath);
        }

        // If it's already a relative path
        if (!filter_var($signature, FILTER_VALIDATE_URL)) {
            return S3Helper::getSignedUrl($signature);
        }

        // Fallback: return the URL as-is
        return $signature;
    }
}
