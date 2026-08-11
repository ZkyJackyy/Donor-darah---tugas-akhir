<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sahabat Donor</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            900: '#7f1d1d',
                        },
                        medical: {
                            blue: '#0ea5e9',
                            soft: '#faf9f7',
                        },
                        ink: '#1f2933',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #faf9f7; color: #1f2933; -webkit-font-smoothing: antialiased; }

        /* Floating Label CSS — sama seperti form admin lainnya */
        .float-input { transition: border-color 0.2s, box-shadow 0.2s; }
        .float-input:focus-within {
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }
        .float-label { transition: all 0.2s; pointer-events: none; }
        .float-input input:focus ~ .float-label,
        .float-input input:not(:placeholder-shown) ~ .float-label {
            transform: translateY(-130%) scale(0.85);
            color: #dc2626;
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-medical-soft">

    <div class="w-full max-w-md mx-auto p-6">
        <!-- Logo & Wordmark -->
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-9 h-9 rounded-md overflow-hidden flex items-center justify-center">
                <img src="{{ asset('logo_apk.png') }}" alt="Logo" class="w-full h-full object-cover">
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">Sahabat<span class="text-brand-600">Donor</span></h1>
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Admin Portal</p>
            </div>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-8">
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 tracking-tight">Masuk ke Sistem</h2>
                <p class="text-sm text-gray-500 mt-1">Gunakan kredensial admin Anda untuk melanjutkan.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md flex items-start gap-3">
                    <svg class="w-4 h-4 text-red-600 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <p class="text-sm font-medium">{{ $errors->first() }}</p>
                </div>
            @endif

            <form action="{{ route('admin.login.attempt') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Email -->
                <div class="relative float-input bg-gray-50 rounded-md border border-gray-200 px-4 pt-6 pb-2">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder=" "
                        class="w-full bg-transparent text-sm text-gray-900 focus:outline-none placeholder-transparent peer">
                    <label for="email" class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                        Alamat Email
                    </label>
                </div>

                <!-- Password -->
                <div class="relative float-input bg-gray-50 rounded-md border border-gray-200 px-4 pt-6 pb-2" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required placeholder=" "
                        class="w-full bg-transparent text-sm text-gray-900 focus:outline-none placeholder-transparent peer pr-10">
                    <label for="password" class="float-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium origin-left">
                        Kata Sandi
                    </label>
                    <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.243M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative flex items-center justify-center">
                            <input type="checkbox" name="remember" class="peer sr-only">
                            <div class="w-4 h-4 bg-white border border-gray-300 rounded group-hover:border-brand-500 peer-checked:bg-brand-600 peer-checked:border-brand-600 transition-colors"></div>
                            <svg class="w-3 h-3 text-white absolute pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-md text-sm transition-colors">
                    Masuk Dashboard
                </button>
            </form>
        </div>

        <div class="mt-6 flex items-center justify-center gap-2">
            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            <p class="text-[11px] text-gray-400 font-medium uppercase tracking-widest">Sistem Akses Tertutup Palang Merah Indonesia</p>
        </div>
    </div>
</body>
</html>
