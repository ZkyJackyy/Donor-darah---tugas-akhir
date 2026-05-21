@extends('layouts.admin')

@section('page_title', 'Buat Permintaan Baru')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.blood-requests.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-brand-600 transition-colors bg-white px-4 py-2 rounded-xl border border-gray-100 shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<style>
    /* Floating Label CSS for Form */
    .float-input { transition: all 0.2s; }
    .float-input:focus-within { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
    .float-label { transition: all 0.2s; pointer-events: none; }
    .float-input input:focus ~ .float-label,
    .float-input input:not(:placeholder-shown) ~ .float-label,
    .float-input select:focus ~ .float-label,
    .float-input select:not([value=""]) ~ .float-label,
    .float-input textarea:focus ~ .float-label,
    .float-input textarea:not(:placeholder-shown) ~ .float-label {
        transform: translateY(-130%) scale(0.85);
        color: #ef4444;
        font-weight: 700;
        background-color: transparent;
        padding: 0 4px;
    }
</style>

<form action="{{ route('admin.blood-requests.store') }}" method="POST" class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
    @csrf
    
    <!-- Left Column: Form Details -->
    <div class="lg:col-span-7 space-y-6">
        <!-- Card 1: Medical Details -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 rounded-bl-full -mr-16 -mt-16 z-0"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-8 pb-4 border-b border-gray-50">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Spesifikasi Darah</h3>
                        <p class="text-xs text-gray-500 font-medium">Tentukan kebutuhan spesifik golongan darah pasien.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Golongan Darah -->
                    <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <select name="blood_type" class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none appearance-none cursor-pointer peer" required>
                            <option value="" disabled selected hidden></option>
                            <option value="A">Golongan A</option>
                            <option value="B">Golongan B</option>
                            <option value="AB">Golongan AB</option>
                            <option value="O">Golongan O</option>
                        </select>
                        <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                            Golongan Darah
                        </label>
                        <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    
                    <!-- Rhesus -->
                    <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <select name="rhesus" class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none appearance-none cursor-pointer peer" required>
                            <option value="" disabled selected hidden></option>
                            <option value="+">Positif (+)</option>
                            <option value="-">Negatif (-)</option>
                        </select>
                        <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                            Rhesus Faktor
                        </label>
                        <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Jumlah Kantong -->
                    <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <input type="number" name="required_bags" min="1" value="{{ old('required_bags', 1) }}" required placeholder=" " 
                            class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none placeholder-transparent peer">
                        <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                            Jumlah Kantong
                        </label>
                    </div>

                    <!-- Tingkat Urgensi -->
                    <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <select name="urgency_level" class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none appearance-none cursor-pointer peer" required>
                            <option value="normal">Normal (Biasa)</option>
                            <option value="urgent">Penting (Mendesak)</option>
                            <option value="critical">Darurat (Kritis)</option>
                        </select>
                        <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left" style="transform: translateY(-130%) scale(0.85); color: #ef4444; font-weight: 700;">
                            Tingkat Urgensi
                        </label>
                        <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Deadline -->
                <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                    <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" required placeholder=" " 
                        class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none placeholder-transparent peer">
                    <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left" style="transform: translateY(-130%) scale(0.85); color: #ef4444; font-weight: 700;">
                        Batas Waktu Terpenuhi
                    </label>
                </div>
            </div>
        </div>

        <!-- Card 2: Location Details -->
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8">
            <div class="flex items-center gap-3 mb-8 pb-4 border-b border-gray-50">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Informasi Instansi</h3>
                    <p class="text-xs text-gray-500 font-medium">Lengkapi detail lokasi rumah sakit atau klinik yang membutuhkan.</p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Hospital Name -->
                <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                    <input type="text" name="hospital_name" value="{{ old('hospital_name') }}" required placeholder=" " 
                        class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none placeholder-transparent peer">
                    <label class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                        Nama Rumah Sakit / Instansi
                    </label>
                </div>

                <!-- Address -->
                <div class="relative float-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                    <textarea name="hospital_address" rows="3" required placeholder=" " 
                        class="w-full bg-transparent text-sm font-bold text-gray-900 focus:outline-none placeholder-transparent peer resize-none">{{ old('hospital_address') }}</textarea>
                    <label class="float-label absolute left-4 top-6 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                        Alamat Lengkap
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Map & Action -->
    <div class="lg:col-span-5 space-y-6">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden flex flex-col h-[520px]">
            <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Titik Koordinat Lokasi
                </h3>
                <p class="text-[11px] text-gray-500 font-medium mt-1 leading-relaxed">
                    Geser pin merah pada peta di bawah ini untuk menentukan titik kordinat pasti rumah sakit. Sistem radius 5KM bergantung pada akurasi titik ini.
                </p>
            </div>
            
            <div class="flex-1 w-full relative">
                <div id="map" class="absolute inset-0 w-full h-full z-10"></div>
                <!-- Loading State Map -->
                <div class="absolute inset-0 flex items-center justify-center bg-gray-100 z-0">
                    <div class="w-8 h-8 border-4 border-gray-300 border-t-brand-500 rounded-full animate-spin"></div>
                </div>
            </div>
            
            <div class="p-6 bg-gray-50/80 border-t border-gray-100">
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', '-0.9471') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', '100.4172') }}">

                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-extrabold py-3.5 rounded-xl text-sm uppercase tracking-widest shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Publikasikan Permintaan
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    const initialLat = parseFloat(latInput.value) || -0.9471; 
    const initialLng = parseFloat(lngInput.value) || 100.4172;

    var map = L.map('map', { zoomControl: false }).setView([initialLat, initialLng], 14);

    // Modern Map Tile
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);
    
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Custom Marker Icon
    var customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div style="background-color:#ef4444; width:20px; height:20px; border-radius:50%; border:3px solid white; box-shadow:0 0 10px rgba(239,68,68,0.5);"></div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    var marker = L.marker([initialLat, initialLng], { 
        draggable: true,
        icon: customIcon
    }).addTo(map);

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
