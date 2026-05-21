<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CODETV') — Free IPTV Channels</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="apple-touch-icon" href="/images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        codetv: {
                            50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0',
                            300: '#86efac', 400: '#4ade80', 500: '#22c55e',
                            600: '#16a34a', 700: '#15803d', 800: '#166534',
                            900: '#14532d', 950: '#052e16',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="bg-gray-950 text-gray-100 font-sans antialiased"
    x-data="{
        user: JSON.parse(localStorage.getItem('codetv_user') || 'null'),
        token: localStorage.getItem('codetv_token') || null,
        showLogin: false,
        showRegister: false,
        authEmail: '',
        authPassword: '',
        authName: '',
        authError: '',
        authLoading: false,
        init() {
            this.$watch('token', val => {
                if (!val) this.user = null;
            });
        },
        async login() {
            this.authLoading = true; this.authError = '';
            try {
                let resp = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.authEmail, password: this.authPassword })
                });
                let data = await resp.json();
                if (!resp.ok) { this.authError = data.message || data.errors?.email?.[0] || 'Login failed'; return; }
                this.token = data.token; this.user = data.user;
                localStorage.setItem('codetv_token', data.token);
                localStorage.setItem('codetv_user', JSON.stringify(data.user));
                this.showLogin = false; this.authEmail = ''; this.authPassword = '';
            } catch(e) { this.authError = 'Connection error'; }
            finally { this.authLoading = false; }
        },
        async register() {
            this.authLoading = true; this.authError = '';
            try {
                let resp = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.authName, email: this.authEmail, password: this.authPassword })
                });
                let data = await resp.json();
                if (!resp.ok) { this.authError = data.message || 'Registration failed'; return; }
                this.token = data.token; this.user = data.user;
                localStorage.setItem('codetv_token', data.token);
                localStorage.setItem('codetv_user', JSON.stringify(data.user));
                this.showRegister = false; this.authName = ''; this.authEmail = ''; this.authPassword = '';
            } catch(e) { this.authError = 'Connection error'; }
            finally { this.authLoading = false; }
        },
        logout() {
            if (this.token) {
                fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.token }
                }).catch(() => {});
            }
            this.token = null; this.user = null;
            localStorage.removeItem('codetv_token');
            localStorage.removeItem('codetv_user');
        }
    }"
    @open-auth.window="showLogin = true">

    <nav class="fixed top-0 z-50 w-full bg-gray-900/95 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="/images/logo.png" alt="CODETV" class="h-12 w-12 rounded-lg">
                </a>
                <div class="hidden md:flex items-center gap-5">
                    <a href="{{ route('home') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition">Home</a>
                    <a href="{{ route('uganda') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition flex items-center gap-1"><span>🇺🇬</span> Uganda</a>
                    <a href="{{ route('sports') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition"><i class="fas fa-futbol mr-1 text-blue-400"></i> Sports</a>
                    <a href="{{ route('international') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition"><i class="fas fa-globe mr-1 text-purple-400"></i> Global</a>
                    <a href="{{ route('browse') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition">Browse</a>
                    <a href="{{ route('guide') }}" class="text-sm text-gray-300 hover:text-codetv-400 transition"><i class="fas fa-calendar-alt mr-1"></i> Guide</a>
                    <template x-if="token">
                        <a href="{{ route('favorites') }}" class="text-sm text-gray-300 hover:text-red-400 transition"><i class="fas fa-heart mr-1"></i> Favorites</a>
                    </template>
                </div>
                <div class="flex items-center gap-3">
                    <template x-if="!token">
                        <button @click="showLogin = true" class="text-sm text-gray-300 hover:text-codetv-400 transition"><i class="fas fa-sign-in-alt mr-1"></i> Sign In</button>
                    </template>
                    <template x-if="token">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-400"><i class="fas fa-user mr-1 text-codetv-400"></i><span x-text="user?.name || ''"></span></span>
                            <button @click="logout" class="text-sm text-gray-500 hover:text-red-400 transition"><i class="fas fa-sign-out-alt"></i></button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-16 min-h-screen">@yield('content')</main>

    <footer class="bg-gray-900 border-t border-gray-800 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-2">
                <img src="/images/logo.png" alt="" class="h-8 w-8 rounded">
            </div>
            <p class="text-gray-500 text-sm">Free IPTV channels from around the world. Not affiliated with any content provider.</p>
            <p class="text-gray-600 text-xs mt-2">&copy; {{ date('Y') }} CODETV. Developed by William Kaiga. All streams are publicly available.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div x-show="showLogin" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm" x-cloak @click.self="showLogin = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">Sign In</h2>
                <button @click="showLogin = false" class="text-gray-500 hover:text-gray-300"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="login" class="space-y-4">
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Email</label>
                    <input type="email" x-model="authEmail" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500" placeholder="you@example.com">
                </div>
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Password</label>
                    <input type="password" x-model="authPassword" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500" placeholder="••••••••">
                </div>
                <div x-show="authError" x-text="authError" class="text-red-400 text-sm bg-red-900/20 rounded-lg px-4 py-2"></div>
                <button type="submit" :disabled="authLoading" class="w-full py-3 bg-codetv-600 hover:bg-codetv-500 disabled:opacity-50 rounded-xl font-medium transition">
                    <i x-show="authLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    <span x-text="authLoading ? 'Signing in...' : 'Sign In'"></span>
                </button>
                <p class="text-center text-sm text-gray-500">Don't have an account? <button type="button" @click="showLogin = false; showRegister = true" class="text-codetv-400 hover:text-codetv-300">Register</button></p>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div x-show="showRegister" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm" x-cloak @click.self="showRegister = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">Create Account</h2>
                <button @click="showRegister = false" class="text-gray-500 hover:text-gray-300"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="register" class="space-y-4">
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Name</label>
                    <input type="text" x-model="authName" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500" placeholder="Your name">
                </div>
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Email</label>
                    <input type="email" x-model="authEmail" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500" placeholder="you@example.com">
                </div>
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Password</label>
                    <input type="password" x-model="authPassword" minlength="8" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-codetv-500" placeholder="Min 8 characters">
                </div>
                <div x-show="authError" x-text="authError" class="text-red-400 text-sm bg-red-900/20 rounded-lg px-4 py-2"></div>
                <button type="submit" :disabled="authLoading" class="w-full py-3 bg-codetv-600 hover:bg-codetv-500 disabled:opacity-50 rounded-xl font-medium transition">
                    <i x-show="authLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    <span x-text="authLoading ? 'Creating account...' : 'Create Account'"></span>
                </button>
                <p class="text-center text-sm text-gray-500">Already have an account? <button type="button" @click="showRegister = false; showLogin = true" class="text-codetv-400 hover:text-codetv-300">Sign In</button></p>
            </form>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
    @stack('scripts')
</body>
</html>
