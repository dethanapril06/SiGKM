@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between py-3 mb-4"><h4 class="fw-bold">Tambah Notulen RTM</h4><a href="{{ route('notulen-rtm.index') }}" class="btn btn-secondary">Kembali</a></div>
<div class="card"><div class="card-body"><form action="{{ route('notulen-rtm.store') }}" method="POST" enctype="multipart/form-data">@csrf
    @include('rtm.notulen.form')
    <button class="btn btn-primary">Simpan Draft</button>
</form></div></div>
@endsection
