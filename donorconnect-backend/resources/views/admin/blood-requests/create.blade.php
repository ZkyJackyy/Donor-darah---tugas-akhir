@extends('layouts.admin')

@section('page_title', 'Buat Permintaan Baru')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.blood-requests.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-800 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        Kembali ke Daftar
    </a>
</div>

<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.blood-requests.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Side: Form Details -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-6">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Detail Medis</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Golongan Darah</label>
                            <select name="blood_type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                                <option value="">Pilih...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Rhesus</label>
                            <select name="rhesus" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                                <option value="">Pilih...</option>
                                <option value="+">+</option>
                                <option value="-">-</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Jumlah Kantong</label>
                            <input type="number" name="required_bags" min="1" value="{{ old('required_bags', 1) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tingkat Urgensi</label>
                            <select name="urgency_level" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Batas Waktu (Deadline)</label>
                        <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-6">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Informasi Lokasi</h3>
                    
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Rumah Sakit</label>
                        <input type="text" name="hospital_name" value="{{ old('hospital_name') }}" placeholder="Contoh: RSUP DR. M. DJAMIL" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</label>
                        <textarea name="hospital_address" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" required>{{ old('hospital_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right Side: Map and Submit -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-1 h-fit overflow-hidden">
                    <div id="map" class="h-80 w-full rounded-md"></div>
                </div>

                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 border-dashed">
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">
                        <span class="font-bold text-gray-700">PENTING:</span> Pastikan titik lokasi di peta sudah sesuai. Titik ini digunakan sistem untuk mencari pendonor terdekat dalam radius 5KM.
                    </p>

                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', '-0.9471') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', '100.4172') }}">

                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-md text-sm uppercase tracking-widest shadow-sm transition duration-150">
                        Publikasikan Permintaan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    const initialLat = parseFloat(latInput.value) || -0.9471; 
    const initialLng = parseFloat(lngInput.value) || 100.4172;

    var map = L.map('map').setView([initialLat, initialLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    });

    marker.on('dragend', function(e) {
        var position = marker.getLatLng();
        latInput.value = position.lat.toFixed(6);
        lngInput.value = position.lng.toFixed(6);
    });
});
</script>
@endpush
@endsection
