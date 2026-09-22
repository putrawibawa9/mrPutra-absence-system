<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use Illuminate\Database\Seeder;

/**
 * Master item hasil pengelompokan judul expense yang sudah ada. Idempotent
 * (updateOrCreate by name). Keywords dipakai untuk peringatan & pencocokan
 * otomatis di command expenses:recategorize.
 *
 * Jalankan SETELAH ExpenseCategorySeeder.
 */
class ExpenseItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ExpenseCategory::query()->pluck('id', 'name');

        // name => [category name, keywords (comma), default_amount|null]
        $items = [
            // Fee Guru (kategori sistem) — dipakai fee otomatis.
            'Fee Guru' => ['Fee Guru', null, null],

            // Software & Langganan
            'Claude Pro' => ['Software & Langganan', 'claude,claude pro', 370000],
            'ChatGPT Plus' => ['Software & Langganan', 'chatgpt,chat gpt,gpt plus,gpt', 350000],
            'CapCut Pro' => ['Software & Langganan', 'capcut', null],
            'Hostinger' => ['Software & Langganan', 'hostinger,hosting,server,hostingan', 191697],
            'Gamma AI' => ['Software & Langganan', 'gamma', null],
            'Boardmaker' => ['Software & Langganan', 'boardmaker', null],

            // Sosmed & Marketing
            'Meta Verified' => ['Sosmed & Marketing', 'meta verified,meta', null],
            'Desain & Cetak Promosi' => ['Sosmed & Marketing', 'xbanner,x-banner,banner,brosur,pricelist,mascot,maskot,design,desain,poster,cetak promosi,dp design,dp mascot', null],
            'Jasa Konten & Sosmed' => ['Sosmed & Marketing', 'konten,editor,sewa editor,gmaps,review,sati', null],
            'Jasa Marketing (Enita)' => ['Sosmed & Marketing', 'enita', null],

            // Bahan Ajar
            'Print & Fotokopi Bahan Ajar' => ['Bahan Ajar', 'print modul,print buku,print materi,print semua buku,fotokopi,fotocopy,fotokopi modul,fotokopi buku,print bahan,worksheet,print,modul,buku', null],

            // Aset & Peralatan
            'Cicilan Robot' => ['Aset & Peralatan', 'robot,cue,maqueen,micro:bit,microbit,robot dash,ongkir robot', 1004126],
            'Cicilan MacBook' => ['Aset & Peralatan', 'macbook', null],
            'Kursi Kerja Owner' => ['Aset & Peralatan', 'kursi', null],
            'Peralatan Kerja' => ['Aset & Peralatan', 'headset,stand laptop,screenguard,screen guard,gunting,ganti screen,iphone', null],

            // Operasional
            'Kuota Internet' => ['Operasional', 'kuota', null],
            'Admin Transfer' => ['Operasional', 'admin tf,biaya tf,tf fee,admin transfer', null],
            'Rekrutmen Guru' => ['Operasional', 'loker,cari guru', null],

            // Owner's Insentif
            'Kopi / WFC Owner' => ["Owner's Insentif", 'kopi,ngopi,wfc,wfh,coffee,tokopi', null],

            // Biaya Volunteer
            'Upah Koordinator Volunteer' => ['Biaya Volunteer', 'dede,urus volunteer,handle volunteer,koordinator volunteer', 250000],
            'Kos & Listrik Volunteer' => ['Biaya Volunteer', 'listrik kos,kos vols,listrik vols,sprei,pulsa listrik,listrik volunteer,kos volunteer', null],

            // R&D Kurikulum (tanpa keyword otomatis — assign manual saja)
            'Pengembangan Kurikulum' => ['R&D Kurikulum', null, null],
        ];

        foreach ($items as $name => [$categoryName, $keywords, $default]) {
            $categoryId = $categories[$categoryName] ?? null;

            if (! $categoryId) {
                // Kategori belum ada (seeder kategori belum jalan) — lewati aman.
                continue;
            }

            ExpenseItem::query()->updateOrCreate(
                ['name' => $name],
                [
                    'expense_category_id' => $categoryId,
                    'keywords' => $keywords,
                    'default_amount' => $default,
                    'is_active' => true,
                ]
            );
        }
    }
}
