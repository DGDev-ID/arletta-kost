<?php

namespace Database\Seeders;

use App\Models\TermsCondition;
use Illuminate\Database\Seeder;

class TermsConditionSeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            'Penyewa wajib menjaga kebersihan, ketertiban, dan keamanan lingkungan kost.',
            'Pembayaran sewa dilakukan sesuai dengan periode yang telah disepakati dan tidak dapat dikembalikan (non-refundable) kecuali disetujui pihak manajemen.',
            'Penyewa dilarang merusak fasilitas kost. Segala kerusakan akibat kelalaian penyewa menjadi tanggung jawab penyewa.',
            'Penyewa tidak diperkenankan memindahkan hak sewa kepada pihak lain tanpa persetujuan tertulis dari manajemen.',
            'Manajemen berhak melakukan inspeksi kamar dengan pemberitahuan terlebih dahulu.',
            'Pelanggaran terhadap syarat dan ketentuan dapat mengakibatkan pemutusan kontrak sewa tanpa pengembalian dana.',
            'Dengan menandatangani dokumen ini, penyewa menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku.',
        ];

        foreach ($terms as $index => $content) {
            TermsCondition::create([
                'order'   => $index + 1,
                'content' => $content,
            ]);
        }
    }
}
