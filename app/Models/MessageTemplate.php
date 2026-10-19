<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'body'])]
class MessageTemplate extends Model
{
    use HasFactory;

    /** Blok rekening (dipakai sebagai placeholder {info_rekening}). */
    public const REKENING_BLOCK = "Pembayaran dapat ditransfer ke salah satu rekening berikut (a.n. I Gede Putra Wibawa):\n"
        ."• Bank Jago — 10180276877\n"
        ."• BRI — 081501035456538";

    /**
     * Daftar template: key => [name, body default, placeholders].
     * Body default dibuat agar output-nya identik dengan pesan lama.
     */
    public static function catalog(): array
    {
        return [
            'billing' => [
                'name' => 'Tagih Pembayaran',
                'placeholders' => ['{nama}', '{kalimat_sisa}', '{kalimat_lanjut}', '{sisa_pertemuan}', '{jumlah_pertemuan}', '{biaya}', '{info_rekening}', '{link_bayar}'],
                'body' => "Halo Bapak/Ibu / Ananda, semoga sehat selalu.\n\n"
                    ."Kami ingin menyampaikan bahwa sisa pertemuan untuk Ananda {nama} {kalimat_sisa}.\n\n"
                    ."{kalimat_lanjut}\n\n"
                    ."{info_rekening}\n\n"
                    ."Untuk bayar via QRIS & rincian pembayaran, buka:\n{link_bayar}\n\n"
                    ."Mohon konfirmasi bukti transfernya setelah pembayaran ya. Terima kasih banyak. 🙏",
            ],
            'inactive_followup' => [
                'name' => 'Follow-up Murid Non-aktif',
                'placeholders' => ['{nama}'],
                'body' => "Halo Bapak/Ibu / Ananda {nama}, semoga sehat selalu.\n\n"
                    ."Kami dari Mr. Putra memperhatikan Ananda belum hadir di beberapa pertemuan les terakhir. "
                    ."Apakah ada kendala atau ada yang bisa kami bantu, misalnya terkait jadwal les?\n\n"
                    ."Kami harap Ananda bisa melanjutkan belajarnya bersama kami. Terima kasih banyak.",
            ],
            'feedback' => [
                'name' => 'Minta Feedback',
                'placeholders' => ['{nama}', '{link_feedback}'],
                'body' => "Halo Kak {nama}, terima kasih ya sudah menjadi bagian dari Mr. Putra Speak.\n\n"
                    ."Boleh minta waktunya sebentar untuk berbagi pesan, kesan, dan saran lewat form singkat ini? "
                    ."Masukan Kakak sangat membantu kami untuk terus berkembang.\n\n"
                    ."{link_feedback}\n\n"
                    ."Terima kasih banyak!",
            ],
            'completed' => [
                'name' => 'Murid Selesai',
                'placeholders' => ['{nama}', '{link_feedback}'],
                'body' => "Halo Bapak/Ibu / Ananda {nama}, semoga sehat selalu.\n\n"
                    ."Selamat! 🎉 Ananda telah menyelesaikan program les bersama Mr. Putra Speak. "
                    ."Terima kasih banyak atas kepercayaan & semangat belajarnya selama ini.\n\n"
                    ."Jika berkenan, kami sangat menghargai masukan Ananda lewat form singkat berikut:\n{link_feedback}\n\n"
                    ."Sukses selalu untuk Ananda. Sampai jumpa lagi! 🙏",
            ],
        ];
    }

    public static function names(): array
    {
        return collect(self::catalog())->map(fn ($t) => $t['name'])->all();
    }

    /** Body efektif untuk sebuah key: dari DB kalau ada, else default. */
    public static function bodyFor(string $key): string
    {
        $row = static::query()->where('key', $key)->first();
        if ($row && filled($row->body)) {
            return $row->body;
        }

        return self::catalog()[$key]['body'] ?? '';
    }

    /**
     * Render template: ambil body efektif lalu ganti placeholder.
     *
     * @param  array<string, string>  $vars  mis. ['nama' => 'Budi']
     */
    public static function render(string $key, array $vars = []): string
    {
        $body = self::bodyFor($key);

        $replace = [];
        foreach ($vars as $token => $value) {
            $replace['{'.$token.'}'] = (string) $value;
        }

        return strtr($body, $replace);
    }
}
