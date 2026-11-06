<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Which requests the activity log records, and how each is described.
 * Every request that changes data is logged; of read-only requests, only
 * logins and the ones that hand data out (exports, downloads, templates)
 * plus a Pengguna Alumni opening their form.
 */
class ActivityDescriptions
{
    private const NAMED = [
        'login' => 'Login',
        'login.nim' => 'Login alumni (NIM & tanggal lahir)',
        'login.sso' => 'Login SSO',
        'logout' => 'Logout',
        'password.update' => 'Mengganti password',
        'password.email' => 'Meminta tautan reset password',
        'password.store' => 'Mereset password',
        'verification.send' => 'Mengirim ulang email verifikasi',
        'profile.update' => 'Memperbarui profil',
        'profile.destroy' => 'Menghapus akun sendiri',

        'admin.users.impersonate' => 'Login sebagai user lain',
        'impersonate.stop' => 'Kembali ke akun Super Admin',
        'admin.users.store' => 'Menambah user',
        'admin.users.update' => 'Mengubah user',
        'admin.users.destroy' => 'Menghapus user',
        'admin.users.bulk-destroy' => 'Menghapus beberapa user',
        'admin.users.import' => 'Mengimpor surveyor',
        'admin.users.template' => 'Mengunduh template surveyor',
        'admin.home-content.update' => 'Mengubah konten halaman depan',

        'alumni.store' => 'Menambah alumni',
        'alumni.update' => 'Mengubah data alumni',
        'alumni.import' => 'Mengimpor data alumni',
        'alumni.import.template' => 'Mengunduh template data alumni',

        'tracer.update' => 'Mengisi/memperbarui kuesioner tracer',
        'tracer.share-link' => 'Membuat tautan form Pengguna Alumni',
        'tracer.import' => 'Mengimpor data tracer',
        'tracer.import.template' => 'Mengunduh template data tracer',

        'employer.show' => 'Membuka form Pengguna Alumni',
        'employer.store' => 'Mengisi kuesioner Pengguna Alumni',
        'employer.import' => 'Mengimpor data Pengguna Alumni',
        'employer.import.template' => 'Mengunduh template data Pengguna Alumni',

        'reports.store' => 'Mengunggah laporan',
        'reports.destroy' => 'Menghapus laporan',
        'reports.download' => 'Mengunduh laporan',
        'reports.auto.download' => 'Membuat laporan tracer otomatis (PDF)',

        'export.tracer' => 'Mengekspor data tracer',
        'export.alumni' => 'Mengekspor data alumni',
        'export.employer' => 'Mengekspor data Pengguna Alumni',
        'export.dashboard' => 'Mengekspor ringkasan dashboard',
    ];

    /** Master data managed through admin.{resource}.{verb} routes. */
    private const MASTER_RESOURCES = [
        'faculties' => 'fakultas',
        'program-studies' => 'program studi',
        'countries' => 'negara',
        'provinces' => 'provinsi',
        'cities' => 'kabupaten/kota',
        'ump' => 'UMP',
        'questions' => 'pertanyaan tambahan',
    ];

    private const MASTER_VERBS = [
        'store' => 'Menambah',
        'update' => 'Mengubah',
        'destroy' => 'Menghapus',
        'import' => 'Mengimpor',
        'template' => 'Mengunduh template',
    ];

    /** Read-only routes worth logging; every other GET is just browsing. */
    private const LOGGED_GETS = [
        'login.sso', 'employer.show', 'reports.download', 'reports.auto.download',
        'export.tracer', 'export.alumni', 'export.employer', 'export.dashboard',
    ];

    public static function shouldLog(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return true;
        }

        $name = $request->route()?->getName();

        return $name !== null && (in_array($name, self::LOGGED_GETS, true) || str_ends_with($name, '.template'));
    }

    /** The route name, or for the unnamed Breeze login POST, "login". */
    public static function action(Request $request): string
    {
        $name = $request->route()?->getName();

        if ($name === null && $request->method() === 'POST' && $request->path() === 'login') {
            return 'login';
        }

        return $name ?? strtolower($request->method()).' /'.ltrim($request->path(), '/');
    }

    public static function describe(string $action, Request $request): string
    {
        if (isset(self::NAMED[$action])) {
            return self::NAMED[$action];
        }

        if (preg_match('/^admin\.([a-z-]+)\.([a-z-]+)$/', $action, $m)
            && isset(self::MASTER_RESOURCES[$m[1]], self::MASTER_VERBS[$m[2]])) {
            return self::MASTER_VERBS[$m[2]].' '.self::MASTER_RESOURCES[$m[1]];
        }

        return $request->method().' /'.ltrim($request->path(), '/');
    }
}
