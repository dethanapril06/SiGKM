<?php

namespace App\Providers;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\Laporan;
use App\Models\NotulenRtm;
use App\Models\RencanaTindakLanjut;
use App\Models\RingkasanPerkuliahan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'indikator_mutu' => IndikatorMutu::class,
            'ikks' => IndikatorKinerjaKegiatanSatuan::class,
        ]);

        View::composer('layouts.partials.sidebar', function ($view) {
            $pendingCount = 0;

            if (auth()->check() && auth()->user()->hasRole('ketua-gkm')) {
                $pendingCount = Laporan::where('jenis_laporan', 'perkuliahan')->where('status', 'diajukan')->count()
                    + NotulenRtm::where('status', 'diajukan')->count();
            }

            $view->with('verificationPendingCount', $pendingCount);
        });
    }
}
