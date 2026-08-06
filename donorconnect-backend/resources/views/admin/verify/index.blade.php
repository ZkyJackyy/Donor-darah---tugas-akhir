@extends('layouts.admin')

@section('page_title', 'Verifikasi Kode Pendonor')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 bg-brand-50 text-brand-600 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-800">Verifikasi Kehadiran Pendonor</h3>
        </div>
        <p class="text-sm text-gray-500 mb-6">Masukkan kode verifikasi 6 karakter yang ditunjukkan pendonor pada tiket digitalnya. Sistem akan otomatis memperbarui status pendonor, kuota kantong darah, dan status permintaan.</p>

        <form action="{{ route('admin.verify.submit') }}" method="POST">
            @csrf
            <label for="kode_verifikasi" class="block text-sm font-semibold text-gray-700 mb-2">Kode Verifikasi</label>
            <input
                type="text"
                name="kode_verifikasi"
                id="kode_verifikasi"
                autofocus
                autocomplete="off"
                maxlength="6"
                placeholder="Mis. A3F9K2"
                required
                style="text-transform:uppercase; letter-spacing: 0.3em;"
                class="w-full text-center text-2xl font-semibold tracking-widest border border-gray-200 rounded-md px-4 py-4 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-mono"
                value="{{ old('kode_verifikasi') }}"
            >
            @error('kode_verifikasi')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full mt-6 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3.5 rounded-md transition-colors">
                Verifikasi
            </button>
        </form>
    </div>
</div>
@endsection
