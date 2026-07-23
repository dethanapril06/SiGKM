@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between py-3 mb-4"><h4 class="fw-bold">Tambah AMI</h4><a href="{{ route('ami.index') }}" class="btn btn-secondary">Kembali</a></div>
<div class="card"><div class="card-body"><form action="{{ route('ami.store') }}" method="POST" enctype="multipart/form-data">@csrf
    @include('ami.form')
    <button class="btn btn-primary">Simpan AMI</button>
</form></div></div>
@endsection
