<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Northeastern College | HRMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #14532d 0%, #15803d 45%, #4ade80 100%);
            overflow-x: hidden;
        }

        .login-auth-shell {
            perspective: 1600px;
        }

        .login-showcase-card {
            opacity: 0;
            transform: translateX(72px) scale(0.985);
            transform-origin: right center;
            animation: login-showcase-swap 0.72s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
        }

        .login-form-card {
            opacity: 0;
            transform: translateX(-72px) scale(0.985);
            transform-origin: left center;
            animation: login-form-swap 0.72s cubic-bezier(0.22, 0.9, 0.2, 1) 0.08s forwards;
        }

        .login-auth-shell.is-exiting .login-showcase-card,
        .login-auth-shell.is-exiting .login-form-card {
            animation-duration: 0.78s;
            animation-timing-function: cubic-bezier(0.16, 0.84, 0.24, 1);
            animation-delay: 0s;
            animation-fill-mode: forwards;
        }

        .login-auth-shell.is-exiting-left .login-showcase-card {
            animation-name: login-showcase-swap-to-right;
        }

        .login-auth-shell.is-exiting-left .login-form-card {
            animation-name: login-form-swap-to-left;
        }

        .login-auth-shell.is-entering-from-left .login-showcase-card {
            animation-name: login-showcase-enter-from-right;
        }

        .login-auth-shell.is-entering-from-left .login-form-card {
            animation-name: login-form-enter-from-left;
        }

        @keyframes login-showcase-swap {
            from {
                opacity: 0;
                transform: translateX(72px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes login-form-swap {
            from {
                opacity: 0;
                transform: translateX(-72px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes login-showcase-swap-to-right {
            from {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            to {
                opacity: 0.28;
                transform: translateX(calc(100% + 1.2rem)) scale(0.975);
            }
        }

        @keyframes login-form-swap-to-left {
            from {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            to {
                opacity: 0.28;
                transform: translateX(calc(-100% - 1.2rem)) scale(0.975);
            }
        }

        @keyframes login-showcase-enter-from-right {
            from {
                opacity: 0;
                transform: translateX(calc(100% + 2rem)) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes login-form-enter-from-left {
            from {
                opacity: 0;
                transform: translateX(calc(-100% - 2rem)) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
    </style>
</head>
<body>

<div data-auth-root class="login-auth-shell min-h-screen flex items-center justify-center
     bg-gradient-to-br from-green-900 via-green-700 to-green-500 px-6">


    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- LEFT CARD -->
        <div class="login-showcase-card bg-white/95 rounded-3xl p-10 shadow-2xl flex flex-col justify-between">

            <!-- Logo -->
        <div class="flex items-center gap-3 mb-10">
            <div class="w-12 h-12 rounded-xl overflow-hidden flex items-center justify-center bg-white">
                <img
                    src="/images/logo.webp"
                    alt="Northeastern College Logo"
                    class="w-full h-full object-contain"
                >
            </div>
            <div>
                <h1 class="font-bold text-gray-900 text-lg">Northeastern College</h1>
                <p class="text-sm text-gray-500">HR Management System</p>
            </div>
        </div>


            <!-- Headline -->
            <div>
                <h2 class="text-4xl font-extrabold text-gray-900 mb-4">
                    Streamline Your Workforce
                </h2>

                <p class="text-gray-600 mb-8 leading-relaxed">
                    Powerful HRIS platform designed to simplify employee management,
                    Attendance tracking, and HR operations for modern businesses.
                </p>

                <!-- Features -->
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-green-600 font-bold">✓</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Employee Management</h3>
                            <p class="text-sm text-gray-500">
                                Centralized employee database with comprehensive profiles
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-bold">✓</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Attendance Tracking</h3>
                            <p class="text-sm text-gray-500">
                                Monitor employee time-in, time-out, and attendance records in real time
                            </p>
                        </div>

                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center">
                            <span class="text-purple-600 font-bold">✓</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Analytics & Reports</h3>
                            <p class="text-sm text-gray-500">
                                Real-time insights and comprehensive reporting
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-6 mt-10 pt-8 border-t">
                <div>
                    <p class="text-2xl font-bold text-gray-900">50K+</p>
                    <p class="text-sm text-gray-500">Active Users</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">99.9%</p>
                    <p class="text-sm text-gray-500">Uptime</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">4.9/5</p>
                    <p class="text-sm text-gray-500">User Rating</p>
                </div>
            </div>

        </div>

        <!-- RIGHT CARD -->
        <div class="login-form-card bg-white rounded-3xl p-10 shadow-2xl flex items-center">

            <div class="w-full max-w-md mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                <p class="text-gray-500 mb-8">Sign in to continue to your account</p>

<form class="space-y-6" method="POST" action = "{{ route('login') }}" >
    @csrf
    <div>
        <label class="text-sm font-medium text-gray-700">Email Address or Employee ID</label>
        <input type="text" name = "email"
               placeholder="john@example.com or EMP-00001"
               class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700">Password</label>
        <input type="password" name = "password"
               placeholder="••••••••"
               class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
    </div>

    <button class="w-full py-3 rounded-xl bg-gradient-to-r from-green-800 via-green-600 to-green-800
     text-white font-semibold hover:opacity-90 transition">
        Sign In →
    </button>

</form>


                <p class="text-center text-sm text-gray-500 mt-8">
                    Don’t have an account?
                    <a href="{{ route('register')}}" data-auth-link="register" class="text-green-800 font-semibold hover:underline">
                        Create one now
                    </a>
                </p>
            </div>

        </div>

    </div>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-auth-root]');
        if (!root) {
            return;
        }

        const transitionKey = 'auth_transition_state_v1';
        const savedState = sessionStorage.getItem(transitionKey);
        if (savedState === 'from-register') {
            root.classList.add('is-entering-from-left');
            sessionStorage.removeItem(transitionKey);
        }

        document.querySelectorAll('[data-auth-link]').forEach((link) => {
            link.addEventListener('click', function (event) {
                const href = this.getAttribute('href');
                if (!href || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                sessionStorage.setItem(transitionKey, 'to-register');
                root.classList.remove('is-entering-from-left');
                root.classList.add('is-exiting', 'is-exiting-left');

                window.setTimeout(() => {
                    window.location.href = href;
                }, 700);
            });
        });
    })();
</script>

</body>
</html>
