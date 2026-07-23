@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between py-3 mb-4"><h4 class="fw-bold">Edit AMI</h4><a href="{{ route('ami.index') }}" class="btn btn-secondary">Kembali</a></div>
<div class="card"><div class="card-body"><form action="{{ route('ami.update', $ami) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
    @include('ami.form')
    <button class="btn btn-primary">Simpan Perubahan</button>
</form></div></div>
@endsection
