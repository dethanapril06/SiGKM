@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between py-3 mb-4"><h4 class="fw-bold">Edit Notulen RTM</h4><a href="{{ route('notulen-rtm.index') }}" class="btn btn-secondary">Kembali</a></div>
@if($notulenRtm->catatan_verifikasi)<div class="alert alert-danger"><strong>Catatan Ketua GKM:</strong> {{ $notulenRtm->catatan_verifikasi }}</div>@endif
<div class="card"><div class="card-body"><form action="{{ route('notulen-rtm.update', $notulenRtm) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
    @include('rtm.notulen.form')
    <button class="btn btn-primary">Simpan Perubahan</button>
</form></div></div>
@endsection
