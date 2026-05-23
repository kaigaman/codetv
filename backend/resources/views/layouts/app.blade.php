<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mamboleo TV') — Mamboleo Online | Free 1 Month Trial</title>
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
                            50: '#e8edf6', 100: '#c5d2e9', 200: '#9eb3da',
                            300: '#718fc7', 400: '#4d72b4', 500: '#355aa1',
                            600: '#1e3d7c', 700: '#132b5a', 800: '#0f2248',
                            900: '#0a1834', 950: '#060e20',
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
<body class="bg-codetv-950 text-gray-100 font-sans antialiased"
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

    <nav class="fixed top-0 z-50 w-full bg-codetv-900/95 backdrop-blur border-b border-codetv-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="/images/logo.png" alt="Mamboleo TV" class="h-12 w-auto rounded-lg">
                </a>
                <div class="hidden md:flex items-center gap-5">
                    <a href="{{ route('home') }}" class="text-sm text-codetv-200 hover:text-white transition">Home</a>
                    <a href="{{ route('uganda') }}" class="text-sm text-codetv-200 hover:text-white transition flex items-center gap-1"><span>🇺🇬</span> Uganda</a>
                    <a href="{{ route('sports') }}" class="text-sm text-codetv-200 hover:text-white transition"><i class="fas fa-futbol mr-1 text-codetv-400"></i> Sports</a>
                    <a href="{{ route('worldcup') }}" class="text-sm text-yellow-400 hover:text-yellow-300 transition"><i class="fas fa-trophy mr-1"></i> World Cup</a>
                    <a href="{{ route('international') }}" class="text-sm text-codetv-200 hover:text-white transition"><i class="fas fa-globe mr-1 text-codetv-400"></i> Global</a>
                    <a href="{{ route('browse') }}" class="text-sm text-codetv-200 hover:text-white transition">Browse</a>
                    <a href="{{ route('guide') }}" class="text-sm text-codetv-200 hover:text-white transition"><i class="fas fa-calendar-alt mr-1"></i> Guide</a>
                    <template x-if="token">
                        <a href="{{ route('favorites') }}" class="text-sm text-codetv-200 hover:text-red-400 transition"><i class="fas fa-heart mr-1"></i> Favorites</a>
                    </template>
                </div>
                <div class="flex items-center gap-3">
                    <template x-if="!token">
                        <button @click="showLogin = true" class="text-sm text-codetv-200 hover:text-white transition"><i class="fas fa-sign-in-alt mr-1"></i> Sign In</button>
                    </template>
                    <template x-if="token">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-codetv-300"><i class="fas fa-user mr-1 text-codetv-400"></i><span x-text="user?.name || ''"></span></span>
                            <button @click="logout" class="text-sm text-codetv-400 hover:text-red-400 transition"><i class="fas fa-sign-out-alt"></i></button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-16 min-h-screen">@yield('content')</main>

    <footer class="bg-codetv-900 border-t border-codetv-800 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-2">
                <img src="/images/logo.png" alt="" class="h-8 w-8 rounded">
            </div>
            <p class="text-codetv-300 text-sm">Code Paid TV channels from around the world. Start your free 1-month trial today.</p>
            <p class="text-codetv-400 text-xs mt-2">&copy; {{ date('Y') }} Mamboleo TV. Developed by William Kaiga. All streams are publicly available.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div x-show="showLogin" class="fixed inset-0 z-[100] flex items-center justify-center bg-codetv-950/80 backdrop-blur-sm" x-cloak @click.self="showLogin = false">
        <div class="bg-codetv-900 border border-codetv-700 rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">Sign In</h2>
                <button @click="showLogin = false" class="text-gray-500 hover:text-gray-300"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="login" class="space-y-4">
                <div>
                    <label class="text-sm text-gray-400 mb-1 block">Email</label>
                    <input type="email" x-model="authEmail" required class="w-full bg-codetv-800 border border-codetv-600 rounded-xl px-4 py-3 text-white placeholder-codetv-300 focus:outline-none focus:border-codetv-400" placeholder="you@example.com">
                </div>
                <div>
                    <label class="text-sm text-codetv-300 mb-1 block">Password</label>
                    <input type="password" x-model="authPassword" required class="w-full bg-codetv-800 border border-codetv-600 rounded-xl px-4 py-3 text-white placeholder-codetv-300 focus:outline-none focus:border-codetv-400" placeholder="••••••••">
                </div>
                <div x-show="authError" x-text="authError" class="text-red-400 text-sm bg-red-900/20 rounded-lg px-4 py-2"></div>
                <button type="submit" :disabled="authLoading" class="w-full py-3 bg-codetv-600 hover:bg-codetv-500 disabled:opacity-50 rounded-xl font-medium transition">
                    <i x-show="authLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    <span x-text="authLoading ? 'Signing in...' : 'Sign In'"></span>
                </button>
                <p class="text-center text-sm text-codetv-300">Don't have an account? <button type="button" @click="showLogin = false; showRegister = true" class="text-codetv-400 hover:text-codetv-300">Register</button></p>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div x-show="showRegister" class="fixed inset-0 z-[100] flex items-center justify-center bg-codetv-950/80 backdrop-blur-sm" x-cloak @click.self="showRegister = false">
        <div class="bg-codetv-900 border border-codetv-700 rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold">Create Account</h2>
                <button @click="showRegister = false" class="text-codetv-300 hover:text-codetv-200"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="register" class="space-y-4">
                <div>
                    <label class="text-sm text-codetv-300 mb-1 block">Name</label>
                    <input type="text" x-model="authName" required class="w-full bg-codetv-800 border border-codetv-600 rounded-xl px-4 py-3 text-white placeholder-codetv-300 focus:outline-none focus:border-codetv-400" placeholder="Your name">
                </div>
                <div>
                    <label class="text-sm text-codetv-300 mb-1 block">Email</label>
                    <input type="email" x-model="authEmail" required class="w-full bg-codetv-800 border border-codetv-600 rounded-xl px-4 py-3 text-white placeholder-codetv-300 focus:outline-none focus:border-codetv-400" placeholder="you@example.com">
                </div>
                <div>
                    <label class="text-sm text-codetv-300 mb-1 block">Password</label>
                    <input type="password" x-model="authPassword" minlength="8" required class="w-full bg-codetv-800 border border-codetv-600 rounded-xl px-4 py-3 text-white placeholder-codetv-300 focus:outline-none focus:border-codetv-400" placeholder="Min 8 characters">
                </div>
                <div x-show="authError" x-text="authError" class="text-red-400 text-sm bg-red-900/20 rounded-lg px-4 py-2"></div>
                <button type="submit" :disabled="authLoading" class="w-full py-3 bg-codetv-600 hover:bg-codetv-500 disabled:opacity-50 rounded-xl font-medium transition">
                    <i x-show="authLoading" class="fas fa-spinner fa-spin mr-2"></i>
                    <span x-text="authLoading ? 'Creating account...' : 'Create Account'"></span>
                </button>
                <p class="text-center text-sm text-codetv-300">Already have an account? <button type="button" @click="showRegister = false; showLogin = true" class="text-codetv-400 hover:text-codetv-300">Sign In</button></p>
            </form>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
    @stack('scripts')
</body>
</html>
