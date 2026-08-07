@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Tambah Keputusan RTM</h4>
        <a href="{{ route('keputusan-rtm.fakultas') }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('keputusan-rtm.store') }}" method="POST">@csrf
                @include('rtm.keputusan.form')
                <button class="btn btn-primary">Simpan Keputusan</button>
            </form>
        </div>
    </div>
@endsection
