<?php

namespace App\Support;

use App\Models\User;

/**
 * Sumber tunggal struktur menu aplikasi.
 *
 * Dipakai oleh sidebar (layouts/app) dan halaman hub menu (menu/show),
 * supaya sidebar tetap ramping: grup berisi banyak item tampil sebagai
 * SATU link yang mengarah ke halaman hub berisi kartu sub-menunya.
 */
class Navigation
{
    /**
     * @return array<int, array{key: string, label: string, description?: string, items: array<int, array<string, mixed>>}>
     */
    public static function sections(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                [
                    'key' => 'overview',
                    'label' => 'Overview',
                    'items' => [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
                    ],
                ],
                [
                    'key' => 'students',
                    'label' => 'Students',
                    'description' => 'Data murid, pendaftaran, dan pencocokan jadwal.',
                    'items' => [
                        ['label' => 'All Students', 'route' => 'students.index', 'pattern' => 'students.*', 'description' => 'Daftar & detail semua murid.'],
                        ['label' => 'Pendaftaran', 'route' => 'registrations.index', 'pattern' => 'registrations.index', 'description' => 'Murid baru yang mendaftar.'],
                        ['label' => 'Pencocokan Jadwal', 'route' => 'schedule-match.index', 'pattern' => 'schedule-match.*', 'description' => 'Cari guru yang cocok dengan preferensi murid.'],
                    ],
                ],
                [
                    'key' => 'teaching',
                    'label' => 'Teaching',
                    'description' => 'Kelas, absensi, guru, jadwal, dan libur.',
                    'items' => [
                        ['label' => 'Kelas Hari Ini', 'route' => 'classes.today', 'pattern' => 'classes.today', 'description' => 'Jadwal kelas hari ini.'],
                        ['label' => 'Kelas', 'route' => 'classrooms.index', 'pattern' => 'classrooms.*', 'description' => 'Daftar kelas & jurnal pembelajaran.'],
                        ['label' => 'Attendances', 'route' => 'attendances.index', 'pattern' => 'attendances.*', 'description' => 'Catat & kelola absensi.'],
                        ['label' => 'Teachers', 'route' => 'teachers.index', 'pattern' => 'teachers.*', 'description' => 'Data & status guru.'],
                        ['label' => 'Jadwal Guru', 'route' => 'teacher-schedules.index', 'pattern' => 'teacher-schedules.*', 'description' => 'Jadwal mengajar per grup.'],
                        ['label' => 'Ketersediaan Guru', 'route' => 'teacher-availabilities.index', 'pattern' => 'teacher-availabilities.*', 'description' => 'Jam kosong guru (bersih).'],
                        ['label' => 'Pengajuan Libur', 'route' => 'teacher-leaves.index', 'pattern' => 'teacher-leaves.*', 'description' => 'Approve libur & rekomendasi pengganti.'],
                    ],
                ],
                [
                    'key' => 'follow-up',
                    'label' => 'Tindak Lanjut',
                    'description' => 'Pantau murid yang perlu di-follow-up.',
                    'items' => [
                        ['label' => 'Token Menipis', 'route' => 'follow-up.low-token', 'pattern' => 'follow-up.low-token', 'description' => 'Murid dengan sisa token sedikit.'],
                        ['label' => 'Murid Sering Absen', 'route' => 'follow-up.absent', 'pattern' => 'follow-up.absent', 'description' => 'Murid yang sering tidak hadir.'],
                        ['label' => 'Murid Non-aktif', 'route' => 'follow-up.inactive', 'pattern' => 'follow-up.inactive', 'description' => 'Murid yang sudah lama tidak les.'],
                        ['label' => 'Feedback Murid', 'route' => 'follow-up.feedback', 'pattern' => 'follow-up.feedback', 'description' => 'Minta & pantau feedback murid.'],
                    ],
                ],
                [
                    'key' => 'academics',
                    'label' => 'Academics',
                    'description' => 'Modul belajar & link materi.',
                    'items' => [
                        ['label' => 'Modules', 'route' => 'learning-modules.index', 'pattern' => 'learning-modules.*', 'description' => 'Modul pembelajaran.'],
                        ['label' => 'Link Materi', 'route' => 'material-links.index', 'pattern' => 'material-links.*', 'description' => 'Kumpulan link materi.'],
                    ],
                ],
                [
                    'key' => 'finance',
                    'label' => 'Finance',
                    'description' => 'Pembayaran, pengeluaran, gaji, dan laporan keuangan.',
                    'items' => [
                        ['label' => 'Payments', 'route' => 'payments.index', 'pattern' => 'payments.*', 'description' => 'Pembayaran & tagihan murid.'],
                        ['label' => 'Expenses', 'route' => 'expenses.index', 'pattern' => 'expenses.*', 'description' => 'Catat pengeluaran.'],
                        ['label' => 'Item Expense', 'route' => 'expense-items.index', 'pattern' => 'expense-items.*', 'description' => 'Master item pengeluaran.'],
                        ['label' => 'Komitmen / Cicilan', 'route' => 'expense-commitments.index', 'pattern' => 'expense-commitments.*', 'description' => 'Pengeluaran berulang & cicilan.'],
                        ['label' => 'Expense Categories', 'route' => 'expense-categories.index', 'pattern' => 'expense-categories.*', 'description' => 'Kategori pengeluaran.'],
                        ['label' => 'Cash Flow', 'route' => 'cash-flow.index', 'pattern' => 'cash-flow.*', 'description' => 'Arus kas masuk & keluar.'],
                        ['label' => 'Gaji Guru', 'route' => 'reports.teacher-salary', 'pattern' => 'reports.teacher-salary', 'description' => 'Rekap & slip gaji guru.'],
                        ['label' => 'Monitoring OpEx', 'route' => 'reports.opex', 'pattern' => 'reports.opex', 'description' => 'Biaya operasional per kelas.'],
                        ['label' => 'Review Bulanan', 'route' => 'reports.review', 'pattern' => 'reports.review', 'description' => 'Ringkasan laba/rugi bulanan.'],
                        ['label' => 'LTV Murid', 'route' => 'reports.ltv', 'pattern' => 'reports.ltv', 'description' => 'Nilai seumur hidup murid.'],
                        ['label' => 'Churn Murid', 'route' => 'reports.churn', 'pattern' => 'reports.churn', 'description' => 'Laju murid berhenti per bulan.'],
                    ],
                ],
                [
                    'key' => 'account',
                    'label' => 'Account',
                    'items' => [
                        ['label' => 'Profile', 'route' => 'profile.edit', 'pattern' => 'profile.*'],
                    ],
                ],
            ];
        }

        // Guru: hanya Absensi yang aktif. Jadwal & Ketersediaan menyusul.
        return [
            [
                'key' => 'absensi',
                'label' => 'Absensi',
                'items' => [
                    ['label' => 'Attendance', 'route' => 'attendances.index', 'pattern' => 'attendances.*'],
                ],
            ],
            [
                'key' => 'jadwal',
                'label' => 'Jadwal',
                'items' => [
                    ['label' => 'Jadwal Saya', 'route' => 'my-schedule.index', 'pattern' => 'my-schedule.*'],
                ],
            ],
            [
                'key' => 'ketersediaan',
                'label' => 'Ketersediaan',
                'items' => [
                    ['label' => 'Ketersediaan', 'route' => 'my-availability.index', 'pattern' => 'my-availability.*'],
                ],
            ],
            [
                'key' => 'libur',
                'label' => 'Libur',
                'items' => [
                    ['label' => 'Ajukan Libur', 'route' => 'my-leave.index', 'pattern' => 'my-leave.*'],
                ],
            ],
        ];
    }

    /**
     * Cari satu section berdasarkan key untuk user tertentu.
     *
     * @return array{key: string, label: string, description?: string, items: array<int, array<string, mixed>>}|null
     */
    public static function section(User $user, string $key): ?array
    {
        foreach (self::sections($user) as $section) {
            if ($section['key'] === $key) {
                return $section;
            }
        }

        return null;
    }
}
