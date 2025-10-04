<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /admin/');
    } else {
        header('Location: /user/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultra Harvest Global - Copy Forex Trades. Harvest Profits Fast.</title>
    <meta name="description" content="Choose a package, press Copy, and let your money grow with Ultra Harvest Global">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * { font-family: 'Poppins', sans-serif; }
        
        .hero-bg {
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.1) 0%, 
                rgba(251, 191, 36, 0.1) 50%, 
                rgba(16, 185, 129, 0.1) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%2310b981" fill-opacity="0.05" points="0,0 1000,300 1000,1000 0,700"/></svg>'),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%23fbbf24" fill-opacity="0.05" points="1000,0 0,400 0,1000 1000,600"/></svg>');
            background-size: cover;
        }
        
        .forex-chart {
            background: linear-gradient(45deg, #10b981, #34d399);
            border-radius: 20px;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
        }
        
        .wheat-field {
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 20px;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 20px 40px rgba(251, 191, 36, 0.3);
        }
        
        .package-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .package-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(251, 191, 36, 0.1));
            z-index: -1;
        }
        
        .glow-text {
            text-shadow: 0 0 20px rgba(16, 185, 129, 0.5);
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.8); }
        }

        /* Mobile Menu Styles */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }

        /* Testimonial Slider Styles */
        .testimonial-container {
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-slide {
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease-in-out;
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
        }
        
        .testimonial-slide.active {
            opacity: 1;
            transform: translateX(0);
            position: relative;
        }

        /* Responsive text sizes */
        @media (max-width: 640px) {
            .hero-title {
                font-size: 2.5rem;
                line-height: 1.1;
            }
            .hero-subtitle {
                font-size: 1.125rem;
            }
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-white">

    <!-- Header / Hero Section -->
    <div class="hero-bg min-h-screen">
        <div class="relative z-10">
            <!-- Navigation -->
            <nav class="container mx-auto px-4 py-4 sm:py-6">
                <div class="flex justify-between items-center">
                    <!-- Centered Logo -->
                    <div class="flex items-center space-x-3 mx-auto lg:mx-0">
                        <div class="w-12 h-12 rounded-full overflow-hidden" style="background: linear-gradient(45deg, #10b981, #fbbf24);">
                            <img src="/ultra%20Harvest%20Logo.jpg" alt="Ultra Harvest Global" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        </div>
                        <div>
                            <!--<h1 class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent">Ultra Harvest</h1>
                            <p class="text-sm text-gray-300">Global</p>-->
                        </div>
                    </div>
                    
                    <!-- Desktop Menu -->
                    <div class="hidden md:flex space-x-6">
                        <a href="#how-it-works" class="text-gray-300 hover:text-emerald-400 transition text-sm">How It Works</a>
                        <a href="#packages" class="text-gray-300 hover:text-emerald-400 transition text-sm">Packages</a>
                        <a href="/login.php" class="text-emerald-400 hover:text-emerald-300 transition text-sm">Login</a>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden text-white text-lg">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div id="mobile-menu" class="mobile-menu fixed top-0 right-0 h-full w-64 bg-gray-900 z-50 md:hidden">
                    <div class="p-6">
                        <div class="flex justify-end mb-8">
                            <button id="mobile-menu-close" class="text-white text-xl">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="space-y-6">
                            <a href="#how-it-works" class="block text-gray-300 hover:text-emerald-400 transition text-lg mobile-menu-link">How It Works</a>
                            <a href="#packages" class="block text-gray-300 hover:text-emerald-400 transition text-lg mobile-menu-link">Packages</a>
                            <a href="/login.php" class="block text-emerald-400 hover:text-emerald-300 transition text-lg mobile-menu-link">Login</a>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu Overlay -->
                <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>
            </nav>

            <!-- Hero Content -->
            <div class="container mx-auto px-4 py-8 sm:py-16">
                <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 items-center min-h-[400px] sm:min-h-[600px]">
                    <!-- Left Side - Main Content -->
                    <div class="text-center lg:text-left">
                        <h1 class="hero-title text-4xl sm:text-5xl lg:text-7xl font-bold mb-4 sm:mb-6 leading-tight">
                            Copy Forex Trades.
                            <span class="bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent glow-text">
                                Harvest Profits
                            </span>
                            Fast.
                        </h1>
                        <p class="hero-subtitle text-lg sm:text-xl lg:text-2xl text-gray-300 mb-6 sm:mb-8 leading-relaxed">
                            Choose a package, press Copy, and let your money grow.
                        </p>
                        
                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center lg:justify-start">
                            <a href="/register.php" class="px-6 py-3 sm:px-8 sm:py-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-black font-semibold text-sm sm:text-base rounded-full hover:from-yellow-400 hover:to-yellow-500 transform hover:scale-105 transition-all duration-300 pulse-glow">
                                <i class="fas fa-rocket mr-2"></i>Register Now
                            </a>
                            <a href="#how-it-works" class="px-6 py-3 sm:px-8 sm:py-4 border-2 border-emerald-500 text-emerald-400 font-semibold text-sm sm:text-base rounded-full hover:bg-emerald-500 hover:text-black transition-all duration-300">
                                <i class="fas fa-play-circle mr-2"></i>Learn How It Works
                            </a>
                        </div>
                    </div>

                    <!-- Right Side - Visual Elements -->
                    <div class="grid grid-cols-2 gap-4 sm:gap-6">
                        <!-- Forex Chart -->
                        <div class="forex-chart float-animation">
                            <div class="text-center">
                                <i class="fas fa-chart-line text-2xl sm:text-4xl text-white mb-2 sm:mb-4"></i>
                                <div class="h-12 sm:h-20 flex items-end justify-between space-x-1">
                                    <div class="bg-white/30 w-2 sm:w-3 h-6 sm:h-8 rounded"></div>
                                    <div class="bg-white/50 w-2 sm:w-3 h-10 sm:h-16 rounded"></div>
                                    <div class="bg-white/40 w-2 sm:w-3 h-8 sm:h-12 rounded"></div>
                                    <div class="bg-white/60 w-2 sm:w-3 h-12 sm:h-20 rounded"></div>
                                    <div class="bg-white/45 w-2 sm:w-3 h-6 sm:h-10 rounded"></div>
                                </div>
                                <p class="text-xs sm:text-sm text-white/80 mt-2 sm:mt-3">Live Forex Data</p>
                            </div>
                        </div>

                        <!-- Wheat Field -->
                        <div class="wheat-field float-animation" style="animation-delay: -3s;">
                            <div class="text-center">
                                <i class="fas fa-seedling text-2xl sm:text-4xl text-white mb-2 sm:mb-4"></i>
                                <div class="flex justify-center space-x-1 mb-2 sm:mb-3">
                                    <div class="w-1.5 sm:w-2 h-6 sm:h-8 bg-white/40 rounded-full"></div>
                                    <div class="w-1.5 sm:w-2 h-8 sm:h-10 bg-white/50 rounded-full"></div>
                                    <div class="w-1.5 sm:w-2 h-4 sm:h-6 bg-white/30 rounded-full"></div>
                                    <div class="w-1.5 sm:w-2 h-9 sm:h-12 bg-white/60 rounded-full"></div>
                                    <div class="w-1.5 sm:w-2 h-6 sm:h-8 bg-white/40 rounded-full"></div>
                                </div>
                                <p class="text-xs sm:text-sm text-white/80">Growing Wealth</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits Row -->
    <section class="py-12 sm:py-16 bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
                <div class="text-center group">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt text-lg sm:text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-sm sm:text-lg mb-1 sm:mb-2">Secure & Transparent</h3>
                    <p class="text-gray-400 text-xs sm:text-sm">Bank-level security with full transparency</p>
                </div>
                
                <div class="text-center group">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-clock text-lg sm:text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-sm sm:text-lg mb-1 sm:mb-2">ROI in 6H–3D</h3>
                    <p class="text-gray-400 text-xs sm:text-sm">Fast returns on your investments</p>
                </div>
                
                <div class="text-center group">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-gradient-to-r from-emerald-500 to-emerald-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-handshake text-lg sm:text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-sm sm:text-lg mb-1 sm:mb-2">Simple Copy System</h3>
                    <p class="text-gray-400 text-xs sm:text-sm">One-click trading made easy</p>
                </div>
                
                <div class="text-center group">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-wallet text-lg sm:text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-sm sm:text-lg mb-1 sm:mb-2">Fast Withdrawals</h3>
                    <p class="text-gray-400 text-xs sm:text-sm">Quick access to your profits</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="py-16 sm:py-20 bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="section-title text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4">
                    Choose Your 
                    <span class="bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent">Growth Path</span>
                </h2>
                <p class="text-lg sm:text-xl text-gray-300">Unlock exclusive trading packages designed for every investor</p>
            </div>

            <!-- Packages Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-12">
                <?php
                $packages = ['Seed', 'Sprout', 'Growth', 'Harvest'];
                $icons = ['🌱', '🌿', '🌳', '🌾'];
                
                for ($i = 0; $i < 4; $i++): ?>
                <div class="package-card rounded-2xl p-4 sm:p-8 text-center relative">
                    <!-- Lock Overlay -->
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                        <div class="text-center px-2">
                            <i class="fas fa-lock text-2xl sm:text-4xl text-yellow-500 mb-2 sm:mb-4"></i>
                            <p class="text-white font-medium text-sm sm:text-base">Sign up to unlock</p>
                            <p class="text-gray-300 text-xs sm:text-sm">package details</p>
                        </div>
                    </div>
                    
                    <div class="text-3xl sm:text-6xl mb-2 sm:mb-4"><?php echo $icons[$i]; ?></div>
                    <h3 class="text-lg sm:text-2xl font-bold mb-2 text-white"><?php echo $packages[$i]; ?></h3>
                    <div class="h-20 sm:h-32 flex items-center justify-center">
                        <div class="space-y-2 opacity-30">
                            <div class="h-3 sm:h-4 bg-white/20 rounded w-3/4 mx-auto"></div>
                            <div class="h-3 sm:h-4 bg-white/20 rounded w-1/2 mx-auto"></div>
                            <div class="h-3 sm:h-4 bg-white/20 rounded w-2/3 mx-auto"></div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- CTA Button -->
            <div class="text-center">
                <a href="/register.php" class="inline-block px-8 sm:px-10 py-3 sm:py-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-black font-bold text-base sm:text-lg rounded-full hover:from-yellow-400 hover:to-yellow-500 transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-unlock mr-2"></i>Create Account to Unlock
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-16 sm:py-20 bg-gradient-to-b from-gray-800 to-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12 sm:mb-16">
                <h2 class="section-title text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4">How It Works</h2>
                <p class="text-lg sm:text-xl text-gray-300">Three simple steps to start growing your wealth</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-6 sm:gap-8">
                <div class="text-center group">
                    <div class="relative mb-6 sm:mb-8">
                        <div class="w-16 h-16 sm:w-24 sm:h-24 mx-auto bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform mb-4">
                            <i class="fas fa-user-plus text-xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="absolute top-0 right-1/2 transform translate-x-8 sm:translate-x-12 w-6 h-6 sm:w-8 sm:h-8 bg-yellow-500 rounded-full flex items-center justify-center text-black font-bold text-sm sm:text-base">1</div>
                    </div>
                    <h3 class="text-lg sm:text-2xl font-bold mb-3 sm:mb-4">Register Account</h3>
                    <p class="text-gray-400 leading-relaxed text-sm sm:text-base">Create your free account in less than 2 minutes. Secure, fast, and completely transparent.</p>
                </div>

                <div class="text-center group">
                    <div class="relative mb-6 sm:mb-8">
                        <div class="w-16 h-16 sm:w-24 sm:h-24 mx-auto bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform mb-4">
                            <i class="fas fa-credit-card text-xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="absolute top-0 right-1/2 transform translate-x-8 sm:translate-x-12 w-6 h-6 sm:w-8 sm:h-8 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-sm sm:text-base">2</div>
                    </div>
                    <h3 class="text-lg sm:text-2xl font-bold mb-3 sm:mb-4">Choose Package</h3>
                    <p class="text-gray-400 leading-relaxed text-sm sm:text-base">Select the perfect trading package that matches your investment goals and risk appetite.</p>
                </div>

                <div class="text-center group">
                    <div class="relative mb-6 sm:mb-8">
                        <div class="w-16 h-16 sm:w-24 sm:h-24 mx-auto bg-gradient-to-r from-emerald-500 to-emerald-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform mb-4">
                            <i class="fas fa-chart-line text-xl sm:text-3xl text-white"></i>
                        </div>
                        <div class="absolute top-0 right-1/2 transform translate-x-8 sm:translate-x-12 w-6 h-6 sm:w-8 sm:h-8 bg-yellow-500 rounded-full flex items-center justify-center text-black font-bold text-sm sm:text-base">3</div>
                    </div>
                    <h3 class="text-lg sm:text-2xl font-bold mb-3 sm:mb-4">Copy Trade & Get ROI</h3>
                    <p class="text-gray-400 leading-relaxed text-sm sm:text-base">Sit back and watch your investment grow with automated trading and guaranteed returns.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 sm:py-20 bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="section-title text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4">
                    What Our <span class="bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent">Traders</span> Say
                </h2>
                <p class="text-lg sm:text-xl text-gray-300">Real success stories from our community</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="testimonial-container relative min-h-[300px] sm:min-h-[250px]">
                    <!-- Testimonial 1 -->
                    <div class="testimonial-slide active bg-gradient-to-r from-emerald-900/50 to-yellow-900/50 rounded-2xl p-6 sm:p-8 border border-emerald-500/20">
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 sm:mb-6 bg-gradient-to-r from-emerald-500 to-emerald-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-xl sm:text-2xl text-white"></i>
                            </div>
                            <blockquote class="text-lg sm:text-xl lg:text-2xl font-medium mb-4 sm:mb-6 text-gray-200 leading-relaxed">
                                "I started with Seed at KSh 500 and got returns in 24 hours. So simple! The platform is incredibly user-friendly and the profits are exactly as promised."
                            </blockquote>
                            <div>
                                <p class="font-semibold text-base sm:text-lg text-white">Sarah K.</p>
                                <p class="text-emerald-400 text-sm sm:text-base">Nairobi, Kenya</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="testimonial-slide bg-gradient-to-r from-yellow-900/50 to-emerald-900/50 rounded-2xl p-6 sm:p-8 border border-yellow-500/20">
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 sm:mb-6 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-xl sm:text-2xl text-white"></i>
                            </div>
                            <blockquote class="text-lg sm:text-xl lg:text-2xl font-medium mb-4 sm:mb-6 text-gray-200 leading-relaxed">
                                "Three months with Ultra Harvest and I've already doubled my initial investment. The Growth package delivered exactly what was promised!"
                            </blockquote>
                            <div>
                                <p class="font-semibold text-base sm:text-lg text-white">Michael O.</p>
                                <p class="text-yellow-400 text-sm sm:text-base">Mombasa, Kenya</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="testimonial-slide bg-gradient-to-r from-emerald-900/50 to-yellow-900/50 rounded-2xl p-6 sm:p-8 border border-emerald-500/20">
                        <div class="text-center">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 sm:mb-6 bg-gradient-to-r from-emerald-500 to-emerald-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-xl sm:text-2xl text-white"></i>
                            </div>
                            <blockquote class="text-lg sm:text-xl lg:text-2xl font-medium mb-4 sm:mb-6 text-gray-200 leading-relaxed">
                                "As a busy professional, I love the copy trading feature. Set it and forget it - my money works while I sleep. Highly recommended!"
                            </blockquote>
                            <div>
                                <p class="font-semibold text-base sm:text-lg text-white">Grace M.</p>
                                <p class="text-emerald-400 text-sm sm:text-base">Kisumu, Kenya</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial Navigation -->
                <div class="flex justify-center mt-6 sm:mt-8 space-x-3">
                    <button class="testimonial-dot w-3 h-3 rounded-full bg-emerald-500 transition-all duration-300" data-slide="0"></button>
                    <button class="testimonial-dot w-3 h-3 rounded-full bg-gray-500 transition-all duration-300" data-slide="1"></button>
                    <button class="testimonial-dot w-3 h-3 rounded-full bg-gray-500 transition-all duration-300" data-slide="2"></button>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Banner -->
    <section class="py-16 sm:py-20 bg-gradient-to-r from-yellow-500 to-yellow-600">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl sm:text-4xl lg:text-6xl font-bold text-black mb-4 sm:mb-6">
                Your harvest begins today
            </h2>
            <p class="text-lg sm:text-xl text-black/80 mb-6 sm:mb-8 max-w-2xl mx-auto">
                Join thousands of successful traders who are already growing their wealth with Ultra Harvest Global
            </p>
            <a href="/register.php" class="inline-block px-8 sm:px-12 py-3 sm:py-5 bg-emerald-600 text-white font-bold text-lg sm:text-xl rounded-full hover:bg-emerald-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                <i class="fas fa-seedling mr-2 sm:mr-3"></i>Register Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 sm:py-12 bg-gray-900 border-t border-gray-700">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-6 sm:gap-8">
                <div>
                    <!-- Centered Logo -->
                    <div class="flex items-center space-x-3 mx-auto lg:mx-0">
                        <div class="w-12 h-12 rounded-full overflow-hidden" style="background: linear-gradient(45deg, #10b981, #fbbf24);">
                            <img src="/ultra%20Harvest%20Logo.jpg" alt="Ultra Harvest Global" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        </div>
                        <div>
                            <!--<h1 class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent">Ultra Harvest</h1>
                            <p class="text-sm text-gray-300">Global</p>-->
                        </div>
                    </div>
                    <p class="text-gray-400 text-base sm:text-lg font-medium mb-3 sm:mb-4">Growing Wealth Together</p>
                    <p class="text-gray-500 text-sm sm:text-base">Your trusted partner in forex trading and wealth creation.</p>
                </div>
                
                <div class="lg:text-center">
                    <h3 class="font-semibold text-base sm:text-lg mb-3 sm:mb-4 text-white">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="/terms.php" class="block text-gray-400 hover:text-emerald-400 transition text-sm sm:text-base">Terms & Conditions</a>
                        <a href="/privacy.php" class="block text-gray-400 hover:text-emerald-400 transition text-sm sm:text-base">Privacy Policy</a>
                        <a href="/help.php" class="block text-gray-400 hover:text-emerald-400 transition text-sm sm:text-base">Help Center</a>
                    </div>
                </div>
                
                <div class="lg:text-right">
                    <h3 class="font-semibold text-base sm:text-lg mb-3 sm:mb-4 text-white">Connect With Us</h3>
                    <div class="flex lg:justify-end space-x-3 sm:space-x-4 mb-3 sm:mb-4">
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                            <i class="fab fa-facebook-f text-white text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                            <i class="fab fa-twitter text-white text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                            <i class="fab fa-instagram text-white text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-yellow-500 to-yellow-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform">
                            <i class="fab fa-whatsapp text-white text-sm"></i>
                        </a>
                    </div>
                    <p class="text-gray-500 text-xs sm:text-sm">
                        © <?php echo date('Y'); ?> Ultra Harvest Global. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript for smooth scrolling and interactions -->
    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');

        function openMobileMenu() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        mobileMenuBtn.addEventListener('click', openMobileMenu);
        mobileMenuClose.addEventListener('click', closeMobileMenu);
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);

        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Testimonial Slider
        const testimonialSlides = document.querySelectorAll('.testimonial-slide');
        const testimonialDots = document.querySelectorAll('.testimonial-dot');
        let currentSlide = 0;

        function showSlide(slideIndex) {
            testimonialSlides.forEach((slide, index) => {
                slide.classList.remove('active');
                if (index === slideIndex) {
                    slide.classList.add('active');
                }
            });

            testimonialDots.forEach((dot, index) => {
                if (index === slideIndex) {
                    dot.classList.remove('bg-gray-500');
                    dot.classList.add('bg-emerald-500');
                } else {
                    dot.classList.remove('bg-emerald-500');
                    dot.classList.add('bg-gray-500');
                }
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % testimonialSlides.length;
            showSlide(currentSlide);
        }

        // Testimonial dot navigation
        testimonialDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        // Auto-advance testimonials
        setInterval(nextSlide, 5000);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navigation
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('backdrop-blur-md', 'bg-gray-900/90');
            } else {
                nav.classList.remove('backdrop-blur-md', 'bg-gray-900/90');
            }
        });

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });

        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileMenu();
            }
        });
    </script>
    <!-- Floating WhatsApp Channel Button -->
<a href="https://whatsapp.com/channel/0029Vb6ZWta17En4fWE1u22P" 
   target="_blank" 
   rel="noopener noreferrer"
   class="fixed bottom-6 right-6 z-50 w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-all duration-300 group"
   style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);">
    <i class="fab fa-whatsapp text-white text-2xl sm:text-3xl"></i>
    <span class="absolute right-full mr-3 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none shadow-lg">
        Join Our Channel
    </span>
</a>

<style>
    /* Pulse animation for WhatsApp button */
    @keyframes whatsapp-pulse {
        0%, 100% { 
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
        }
        50% { 
            box-shadow: 0 4px 30px rgba(34, 197, 94, 0.8);
        }
    }
    
    a[href*="whatsapp.com/channel"] {
        animation: whatsapp-pulse 2s ease-in-out infinite;
    }
</style>
</body>
</html>