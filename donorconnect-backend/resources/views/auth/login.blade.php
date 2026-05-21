<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - DonorConnect</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        
        /* Floating Label CSS */
        .floating-input {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .floating-input:focus-within {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        .floating-label {
            transition: all 0.2s;
            pointer-events: none;
        }
        .floating-input input:focus ~ .floating-label,
        .floating-input input:not(:placeholder-shown) ~ .floating-label {
            transform: translateY(-130%) scale(0.85);
            color: #ef4444;
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-50">
    
    <!-- Abstract Medical Background Objects -->
    <div class="absolute -top-[20%] -left-[10%] w-[50vw] h-[50vw] rounded-full bg-brand-50 blur-3xl opacity-60 mix-blend-multiply"></div>
    <div class="absolute top-[20%] -right-[10%] w-[40vw] h-[40vw] rounded-full bg-blue-50 blur-3xl opacity-60 mix-blend-multiply"></div>
    <div class="absolute -bottom-[20%] left-[20%] w-[60vw] h-[60vw] rounded-full bg-red-50/50 blur-3xl opacity-60 mix-blend-multiply"></div>

    <div class="w-full max-w-5xl mx-auto p-6 relative z-10 flex items-center justify-center">
        <!-- Main Card -->
        <div class="w-full max-w-4xl bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-2xl shadow-brand-500/10 border border-white/50 overflow-hidden flex flex-col md:flex-row">
            
            <!-- Left Side: Branding / Illustration -->
            <div class="w-full md:w-5/12 bg-gradient-to-br from-brand-600 to-brand-900 p-10 flex flex-col justify-between text-white relative overflow-hidden hidden md:flex">
                <div class="absolute top-0 right-0 w-full h-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-20"></div>
                <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-white opacity-10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-brand-600 mb-6 shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight mb-2">Donor<span class="text-brand-200">Connect</span></h1>
                    <p class="text-brand-100 font-medium text-sm leading-relaxed">Sistem Manajemen & Permintaan Darah Terpadu PMI.</p>
                </div>

                <div class="relative z-10 bg-black/10 backdrop-blur-md border border-white/10 rounded-2xl p-5">
                    <p class="text-xs text-brand-100 italic leading-relaxed">
                        "Setetes darah Anda berarti nyawa bagi mereka. Kelola data pendonor dan permintaan secara real-time dengan efisien."
                    </p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full md:w-7/12 p-8 md:p-14">
                <div class="mb-10 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Masuk ke Sistem</h2>
                    <p class="text-sm text-gray-500 mt-2">Gunakan kredensial admin Anda untuk melanjutkan.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-8 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl flex items-start gap-3 animate-pulse">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        <span class="text-sm font-medium">{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('admin.login.attempt') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <!-- Floating Email -->
                    <div class="relative floating-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder=" " 
                            class="w-full bg-transparent text-sm text-gray-900 focus:outline-none placeholder-transparent peer">
                        <label for="email" class="floating-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm origin-left peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400">
                            Alamat Email
                        </label>
                    </div>
                    
                    <!-- Floating Password -->
                    <div class="relative floating-input bg-gray-50 rounded-xl border border-gray-200 px-4 pt-6 pb-2">
                        <input type="password" id="password" name="password" required placeholder=" " 
                            class="w-full bg-transparent text-sm text-gray-900 focus:outline-none placeholder-transparent peer">
                        <label for="password" class="floating-label absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm origin-left peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400">
                            Kata Sandi
                        </label>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative flex items-center justify-center">
                                <input type="checkbox" name="remember" class="peer sr-only">
                                <div class="w-5 h-5 bg-gray-100 border border-gray-300 rounded group-hover:border-brand-500 peer-checked:bg-brand-500 peer-checked:border-brand-500 transition-colors"></div>
                                <svg class="w-3.5 h-3.5 text-white absolute pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-600 group-hover:text-gray-900">Ingat Saya</span>
                        </label>
                        <a href="#" class="text-sm font-bold text-brand-600 hover:text-brand-800 transition-colors">Lupa sandi?</a>
                    </div>
                    
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 transition-all transform hover:-translate-y-0.5 mt-4">
                        Masuk Dashboard
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-gray-100 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <p class="text-[11px] text-gray-400 font-medium uppercase tracking-widest">Sistem Akses Tertutup Palang Merah Indonesia</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
