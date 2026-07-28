<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\AmiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\EvaluasiIndikatorController;
use App\Http\Controllers\GkmMembershipController;
use App\Http\Controllers\IndikatorMutuController;
use App\Http\Controllers\JadwalMonevController;
use App\Http\Controllers\JadwalRtmController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KeputusanRtmController;
use App\Http\Controllers\KinerjaProgramStudiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotulenRtmController;
use App\Http\Controllers\PerkuliahanController;
use App\Http\Controllers\RencanaTindakLanjutController;
use App\Http\Controllers\RingkasanPerkuliahanController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StandarMutuController;
use App\Http\Controllers\TahunAkademikController;
use App\Http\Controllers\TemuanController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [DashboardController::class, 'profile'])
        ->name('profile.edit');
    Route::patch('/profile/update', [DashboardController::class, 'updateProfile'])
        ->name('profile.update');
    Route::post('/profile/password', [DashboardController::class, 'updatePassword'])
        ->name('profile.password');
    Route::delete('/profile', [DashboardController::class, 'destroyProfile'])
        ->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
});

Route::middleware(['auth', 'role:ketua-gkm'])
    ->prefix('ketua-gkm')
    ->name('ketua-gkm.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'ketuaGkm'])
            ->name('dashboard');

    });

Route::middleware(['auth', 'role:ketua-gkm'])
    ->get('/verifikasi', [VerifikasiController::class, 'index'])
    ->name('verifikasi.index');

Route::middleware(['auth', 'role:anggota-gkm'])
    ->prefix('anggota-gkm')
    ->name('anggota-gkm.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'anggotaGkm'])
            ->name('dashboard');
    });

Route::middleware(['auth', 'role:koordinator-prodi'])
    ->prefix('koordinator-prodi')
    ->name('koordinator-prodi.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'koordinatorProdi'])
            ->name('dashboard');
    });

Route::middleware(['auth', 'role:ketua-gkm,anggota-gkm'])
    ->group(function () {
        Route::patch('/tahun-akademik/{tahunAkademik}/set-active', [TahunAkademikController::class, 'setActive'])
            ->name('tahun-akademik.set-active');

        Route::resource('tahun-akademik', TahunAkademikController::class)
            ->except(['show']);

        Route::patch('/semester/{semester}/set-active', [SemesterController::class, 'setActive'])
            ->name('semester.set-active');

        Route::resource('semester', SemesterController::class)
            ->except(['show']);

        Route::resource('dosen', DosenController::class)
            ->except(['show']);

        Route::resource('mata-kuliah', MataKuliahController::class)
            ->except(['show']);

        Route::resource('kelas', KelasController::class)
            ->except(['show']);

        Route::resource('perkuliahan', PerkuliahanController::class)
            ->except(['show']);

        Route::resource('termin', TerminController::class)
            ->except(['show']);

        Route::patch('/akun/{akun}/toggle-status', [AkunController::class, 'toggleStatus'])
            ->name('akun.toggle-status');

        Route::resource('akun', AkunController::class)
            ->parameters(['akun' => 'akun'])
            ->except(['show']);

        Route::resource('gkm-membership', GkmMembershipController::class)
            ->except(['show']);

        Route::resource('standar-mutu', StandarMutuController::class)
            ->except(['show']);

        Route::resource('indikator-mutu', IndikatorMutuController::class)
            ->except(['show']);

        Route::patch('/jadwal-monev/{jadwalMonev}/set-active', [JadwalMonevController::class, 'setActive'])
            ->name('jadwal-monev.set-active');

        Route::patch('/jadwal-monev/{jadwalMonev}/finish', [JadwalMonevController::class, 'finish'])
            ->name('jadwal-monev.finish');

        Route::resource('jadwal-monev', JadwalMonevController::class)
            ->parameters(['jadwal-monev' => 'jadwalMonev'])
            ->except(['index', 'show']);

        Route::resource('jadwal-rtm', JadwalRtmController::class)
            ->parameters(['jadwal-rtm' => 'jadwalRtm'])
            ->except(['index', 'show']);

        Route::get('/notulen-rtm/{notulenRtm}/download-pdf', [NotulenRtmController::class, 'downloadPdf'])
            ->name('notulen-rtm.download-pdf');
        Route::patch('/notulen-rtm/{notulenRtm}/ajukan', [NotulenRtmController::class, 'submit'])
            ->name('notulen-rtm.ajukan');
        Route::patch('/notulen-rtm/{notulenRtm}/verifikasi', [NotulenRtmController::class, 'verify'])
            ->name('notulen-rtm.verifikasi');
        Route::patch('/notulen-rtm/{notulenRtm}/tolak', [NotulenRtmController::class, 'reject'])
            ->name('notulen-rtm.tolak');
        Route::resource('notulen-rtm', NotulenRtmController::class)
            ->parameters(['notulen-rtm' => 'notulenRtm']);

        Route::resource('keputusan-rtm', KeputusanRtmController::class)
            ->parameters(['keputusan-rtm' => 'keputusanRtm']);
    });

Route::middleware(['auth', 'role:ketua-gkm,anggota-gkm,koordinator-prodi'])
    ->group(function () {
        Route::get('/jadwal-monev', [JadwalMonevController::class, 'index'])
            ->name('jadwal-monev.index');
        Route::get('/jadwal-monev/{jadwalMonev}', [JadwalMonevController::class, 'show'])
            ->name('jadwal-monev.show');
        Route::get('/jadwal-rtm', [JadwalRtmController::class, 'index'])
            ->name('jadwal-rtm.index');
        Route::get('/jadwal-rtm/{jadwalRtm}', [JadwalRtmController::class, 'show'])
            ->name('jadwal-rtm.show');

        Route::get('/laporan/perkuliahan', [LaporanController::class, 'perkuliahan'])
            ->name('laporan.perkuliahan');
        Route::post('/laporan/perkuliahan/submit', [LaporanController::class, 'submitPerkuliahan'])
            ->name('laporan.perkuliahan.submit');
        Route::patch('/laporan/{laporan}/verifikasi', [LaporanController::class, 'verifikasiLaporan'])
            ->name('laporan.verifikasi');
        Route::patch('/laporan/{laporan}/batalkan-verifikasi', [LaporanController::class, 'batalkanVerifikasi'])
            ->name('laporan.batalkan-verifikasi');
        Route::patch('/laporan/{laporan}/tolak', [LaporanController::class, 'tolakLaporan'])
            ->name('laporan.tolak');
        Route::get('/laporan/perkuliahan/excel', [LaporanController::class, 'exportPerkuliahan'])
            ->name('laporan.perkuliahan.excel');
        Route::get('/laporan/standar-mutu', [LaporanController::class, 'standarMutu'])
            ->name('laporan.standar-mutu');
        Route::get('/laporan/standar-mutu/excel', [LaporanController::class, 'exportStandarMutu'])
            ->name('laporan.standar-mutu.excel');
        Route::get('/laporan/rtl/fakultas', [LaporanController::class, 'rtlFakultas'])
            ->name('laporan.rtl.fakultas');
        Route::get('/laporan/rtl/fakultas/excel', [LaporanController::class, 'exportRtlFakultas'])
            ->name('laporan.rtl.fakultas.excel');
        Route::get('/laporan/rtl/prodi', [LaporanController::class, 'rtlProdi'])
            ->name('laporan.rtl.prodi');
        Route::get('/laporan/rtl/prodi/excel', [LaporanController::class, 'exportRtlProdi'])
            ->name('laporan.rtl.prodi.excel');
        Route::get('/laporan/rtm/fakultas', [LaporanController::class, 'rtmFakultas'])
            ->name('laporan.rtm.fakultas');
        Route::get('/laporan/rtm/fakultas/excel', [LaporanController::class, 'exportRtmFakultas'])
            ->name('laporan.rtm.fakultas.excel');
        Route::get('/laporan/rtm/prodi', [LaporanController::class, 'rtmProdi'])
            ->name('laporan.rtm.prodi');
        Route::get('/laporan/rtm/prodi/excel', [LaporanController::class, 'exportRtmProdi'])
            ->name('laporan.rtm.prodi.excel');

        Route::patch('/ringkasan-perkuliahan/{ringkasanPerkuliahan}/submit', [RingkasanPerkuliahanController::class, 'submit'])
            ->name('ringkasan-perkuliahan.submit');

        Route::patch('/ringkasan-perkuliahan/{ringkasanPerkuliahan}/verify', [RingkasanPerkuliahanController::class, 'verify'])
            ->name('ringkasan-perkuliahan.verify');

        Route::patch('/ringkasan-perkuliahan/{ringkasanPerkuliahan}/reject', [RingkasanPerkuliahanController::class, 'reject'])
            ->name('ringkasan-perkuliahan.reject');

        Route::resource('ringkasan-perkuliahan', RingkasanPerkuliahanController::class)
            ->parameters(['ringkasan-perkuliahan' => 'ringkasanPerkuliahan']);

        Route::resource('evaluasi-indikator', EvaluasiIndikatorController::class)
            ->parameters(['evaluasi-indikator' => 'evaluasiIndikator']);

        Route::resource('temuan-evaluasi', TemuanController::class)
            ->parameters(['temuan-evaluasi' => 'temuan']);

        Route::resource('rtl', RencanaTindakLanjutController::class)
            ->parameters(['rtl' => 'rtl']);
    });

Route::middleware(['auth', 'role:ketua-gkm,anggota-gkm,koordinator-prodi'])
    ->group(function () {
        Route::get('/kinerja-program-studi', [KinerjaProgramStudiController::class, 'index'])
            ->name('kinerja-program-studi.index');
        Route::get('/kinerja-program-studi/{jenis}/create', [KinerjaProgramStudiController::class, 'create'])
            ->name('kinerja-program-studi.create');
        Route::post('/kinerja-program-studi/{jenis}', [KinerjaProgramStudiController::class, 'store'])
            ->name('kinerja-program-studi.store');
        Route::get('/kinerja-program-studi/{jenis}/{id}/edit', [KinerjaProgramStudiController::class, 'edit'])
            ->name('kinerja-program-studi.edit');
        Route::put('/kinerja-program-studi/{jenis}/{id}', [KinerjaProgramStudiController::class, 'update'])
            ->name('kinerja-program-studi.update');
        Route::delete('/kinerja-program-studi/{jenis}/{id}', [KinerjaProgramStudiController::class, 'destroy'])
            ->name('kinerja-program-studi.destroy');

        Route::post('/ami/{ami}/dokumen', [AmiController::class, 'storeDocument'])
            ->name('ami.dokumen.store');
        Route::delete('/ami/dokumen/{dokumenAmi}', [AmiController::class, 'destroyDocument'])
            ->name('ami.dokumen.destroy');
        Route::resource('ami', AmiController::class);
    });

require __DIR__.'/auth.php';
