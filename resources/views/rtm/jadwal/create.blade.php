@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center py-3 mb-4">
    <h4 class="fw-bold mb-0">Tambah Jadwal RTM</h4>
    <a href="{{ route('jadwal-rtm.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card"><div class="card-body">
    <form action="{{ route('jadwal-rtm.store') }}" method="POST">
        @csrf
        @include('rtm.jadwal.form')
        <button class="btn btn-primary" type="submit">Simpan</button>
    </form>
</div></div>
@endsection
