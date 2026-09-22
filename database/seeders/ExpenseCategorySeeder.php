<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * Menata ulang kategori expense secara idempotent (updateOrCreate). TIDAK pernah
 * menghapus/menonaktifkan kategori. Kategori 'Tools' di-rename jadi
 * 'Software & Langganan' bila ada. Aman dijalankan berulang.
 */
class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1) Rename 'Tools' -> 'Software & Langganan' (sekali, kalau belum ada nama barunya).
        $tools = ExpenseCategory::query()->where('name', 'Tools')->first();
        if ($tools && ! ExpenseCategory::query()->where('name', 'Software & Langganan')->exists()) {
            $tools->update(['name' => 'Software & Langganan']);
        }

        $F = ExpenseCategory::COST_FIXED;
        $V = ExpenseCategory::COST_VARIABLE;

        // name => [cost_behavior, is_system, notes]
        $categories = [
            'Fee Guru' => [$V, true, 'masuk sini: fee guru otomatis dari absensi; BUKAN: input manual apa pun (dikelola sistem).'],
            'Bahan Ajar' => [$V, false, 'masuk sini: print/fotokopi & beli buku/modul untuk mengajar; BUKAN: pengembangan materi baru (R&D) atau langganan software.'],
            'Biaya Volunteer' => [$F, false, 'masuk sini: upah koordinator, kos/listrik/konsumsi volunteer; BUKAN: fee guru atau biaya marketing.'],
            'Sosmed & Marketing' => [$V, false, 'masuk sini: iklan, desain & cetak promosi, jasa konten/editor sosmed; BUKAN: langganan software atau biaya hidup volunteer.'],
            'Software & Langganan' => [$F, false, 'masuk sini: langganan/aplikasi (Claude, ChatGPT, Hostinger, CapCut, Meta Verified, Gamma); BUKAN: aset fisik atau jasa orang.'],
            'Aset & Peralatan' => [$F, false, 'masuk sini: aset/alat & cicilannya (MacBook, robot, kursi, headset, stand); BUKAN: langganan software atau bahan habis pakai.'],
            'R&D Kurikulum' => [$V, false, 'masuk sini: pengembangan materi/kurikulum BARU; BUKAN: cetak/fotokopi bahan ajar rutin (Bahan Ajar).'],
            'Operasional' => [$V, false, 'masuk sini: kuota, biaya admin/transfer, rekrutmen, ongkos operasional umum; BUKAN: item yang punya kategori khusus.'],
            "Owner's Insentif" => [$V, false, 'masuk sini: kopi/WFC & insentif owner; BUKAN: operasional kantor atau biaya volunteer.'],
            'Partnership' => [$V, false, 'masuk sini: kerja sama pihak LUAR; BUKAN: koordinator/volunteer internal (Biaya Volunteer).'],
        ];

        foreach ($categories as $name => [$behavior, $isSystem, $notes]) {
            ExpenseCategory::query()->updateOrCreate(
                ['name' => $name],
                [
                    'cost_behavior' => $behavior,
                    'is_system' => $isSystem,
                    'notes' => $notes,
                    'is_active' => true,
                ]
            );
        }
    }
}
