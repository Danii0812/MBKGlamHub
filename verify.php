<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email - Make Up By Kyleen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lavender: {
                            50: '#fafaff',
                            100: '#f5f5fa',
                            200: '#ececf7',
                            300: '#e6e6fa',
                            400: '#d8d1e8',
                            500: '#c2b6d9',
                            600: '#a79dbf',
                            700: '#8e83a3',
                            800: '#756a86',
                            900: '#5d516c'
                        },
                        plum: {
                            50: '#f9f2f7',
                            100: '#f1e3ef',
                            200: '#e0c5dc',
                            300: '#c89ac1',
                            400: '#a06c9e',
                            500: '#804f7e',
                            600: '#673f68',
                            700: '#4b2840',
                            800: '#3c1f33',
                            900: '#2c1726'
                        }
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                
                    }
                }
            }
        }
    </script>
    
    <style>
        .gradient-text {
            background: linear-gradient(to right, #804f7e, #673f68);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-bg {
            background: linear-gradient(to right, #804f7e, #673f68);
        }
        .gradient-bg:hover {
            background: linear-gradient(to right, #673f68, #4b2840);
        }
        .backdrop-blur {
            backdrop-filter: blur(12px);
        }
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Custom SweetAlert styling */
        .swal-popup-custom {
            border-radius: 1.5rem !important;
            border: 2px solid #e6e6fa !important;
        }
        
        .swal-title-custom {
            color: #804f7e !important;
            font-family: font-family: 'Poppins', sans-serif;
        }
        
        .swal-button-custom {
            background: linear-gradient(to right, #804f7e, #673f68) !important;
            border: none !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            transition: all 0.3s ease !important;
        }
        
        .swal-button-custom:hover {
            background: linear-gradient(to right, #673f68, #4b2840) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(128, 79, 126, 0.3) !important;
        }
        
        .swal-cancel-button-custom {
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            border: 2px solid #e6e6fa !important;
            background: transparent !important;
            color: #6b7280 !important;
        }
        
        .swal-cancel-button-custom:hover {
            background: #f5f5fa !important;
            border-color: #c2b6d9 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-white to-plum-50 font-poppins">

<!-- Header -->
 <header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
        <div class="container mx-auto px-4 py-4">
          <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <img src="logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto">
          </div>
            <nav class="hidden md:flex items-center space-x-8">
                <a href="homepage.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Home</a>
                <a href="signup.php" class="border-2 border-lavender-300 text-plum-700 hover:bg-lavender-50 px-4 py-2 rounded-md font-medium transition-all bg-transparent">
                    Sign Up
                </a>
            </nav>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Logo/Brand Section -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <i class="fas fa-shield-check text-3xl text-plum-600"></i>
                <span class="text-3xl font-bold gradient-text >Email Verification</span>
            </div>
            <span class="inline-flex items-center bg-lavender-100 text-plum-700 px-4 py-2 rounded-full text-sm font-medium mb-4">
                <i class="fas fa-envelope-circle-check mr-2"></i>
                Verify Your Account
            </span>
        </div>

        <!-- Verification Form -->
        <div class="bg-white rounded-3xl shadow-2xl border border-lavender-200 p-8">
            <h2 class="text-3xl font-bold text-center mb-6 gradient-text">Enter Verification Code</h2>
            <p class="text-gray-600 text-center mb-8">
                We've sent a verification code to your email address. Please enter it below to activate your account.
            </p>
            
            <form method="POST" class="space-y-6" id="verificationForm">
                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-plum-600"></i>
                        Email Address *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           placeholder="Enter your email address"
                           class="w-full px-4 py-3 border border-lavender-300 rounded-lg focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300 bg-white">
                </div>

                <!-- OTP Code -->
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key mr-2 text-plum-600"></i>
                        Verification Code *
                    </label>
                    <input type="text" 
                           id="otp" 
                           name="otp" 
                           required
                           placeholder="Enter 6-digit code"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           class="w-full px-4 py-3 border border-lavender-300 rounded-lg focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300 bg-white text-center text-2xl font-mono tracking-widest">
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full gradient-bg text-white py-4 text-lg rounded-lg font-medium transition-all duration-300 hover:shadow-lg transform hover:scale-105">
                    <i class="fas fa-check-circle mr-2"></i>
                    Verify Email
                </button>
            </form>

            <!-- Resend Code -->
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">
                    Didn't receive the code?
                </p>
                <button onclick="resendCode()" 
                        class="text-plum-600 hover:text-plum-700 font-medium transition-colors underline">
                    <i class="fas fa-redo mr-2"></i>
                    Resend Verification Code
                </button>
            </div>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already verified? 
                    <a href="login.html" class="text-plum-600 hover:text-plum-700 font-medium transition-colors">
                        Sign In
                    </a>
                </p>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center text-sm text-gray-600">
            <p>
                Need help? 
                <a href="contact.php" class="text-plum-600 hover:text-plum-700 transition-colors">Contact Support</a>
            </p>
        </div>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $otp   = $_POST['otp'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();
    
    if ($user) {
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL WHERE email = ?");
        $update->execute([$email]);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Email Verified Successfully!',
                    html: 'Welcome to <strong>Make Up By Kyleen</strong>!<br><br>Your account has been activated and you can now access all our services.',
                    confirmButtonText: 'Sign In Now',
                    confirmButtonColor: '#804f7e',
                    allowOutsideClick: false,
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    },
                    customClass: {
                        popup: 'swal-popup-custom',
                        title: 'swal-title-custom',
                        confirmButton: 'swal-button-custom'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    html: 'The verification code you entered is <strong>invalid</strong> or has <strong>expired</strong>.<br><br>Please check your email and try again, or request a new code.',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#804f7e',
                    showCancelButton: true,
                    cancelButtonText: 'Resend Code',
                    cancelButtonColor: '#6b7280',
                    customClass: {
                        popup: 'swal-popup-custom',
                        title: 'swal-title-custom',
                        confirmButton: 'swal-button-custom',
                        cancelButton: 'swal-cancel-button-custom'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Clear the form and focus on OTP field
                        document.getElementById('otp').value = '';
                        document.getElementById('otp').focus();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        resendCode();
                    }
                });
            });
        </script>";
    }
}
?>

<script>
    // Auto-format OTP input (numbers only)
    document.getElementById('otp').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Auto-submit when 6 digits are entered
    document.getElementById('otp').addEventListener('input', function(e) {
        if (this.value.length === 6) {
            // Optional: Auto-submit after a short delay
            setTimeout(() => {
                if (this.value.length === 6) {
                    document.getElementById('verificationForm').submit();
                }
            }, 500);
        }
    });

    // Resend code function
    function resendCode() {
        const email = document.getElementById('email').value;
        
        if (!email) {
            Swal.fire({
                icon: 'warning',
                title: 'Email Required',
                text: 'Please enter your email address first.',
                confirmButtonColor: '#804f7e',
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom',
                    confirmButton: 'swal-button-custom'
                }
            });
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Resend Verification Code?',
            html: `We'll send a new verification code to:<br><strong>${email}</strong>`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Resend',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#804f7e',
            cancelButtonColor: '#6b7280',
            customClass: {
                popup: 'swal-popup-custom',
                title: 'swal-title-custom',
                confirmButton: 'swal-button-custom',
                cancelButton: 'swal-cancel-button-custom'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Sending...',
                    html: 'Please wait while we send your new verification code.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    customClass: {
                        popup: 'swal-popup-custom'
                    }
                });

                // Here you would make an AJAX call to resend the OTP
                // For now, we'll simulate it with a timeout
                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent!',
                        html: `A new verification code has been sent to:<br><strong>${email}</strong>`,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#804f7e',
                        customClass: {
                            popup: 'swal-popup-custom',
                            title: 'swal-title-custom',
                            confirmButton: 'swal-button-custom'
                        }
                    });
                }, 2000);
            }
        });
    }

    // Form validation
    document.getElementById('verificationForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const otp = document.getElementById('otp').value;
        
        if (!email || !otp) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please fill in both your email address and verification code.',
                confirmButtonColor: '#804f7e',
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom',
                    confirmButton: 'swal-button-custom'
                }
            });
            return false;
        }
        
        if (otp.length !== 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Code',
                text: 'Verification code must be exactly 6 digits.',
                confirmButtonColor: '#804f7e',
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom',
                    confirmButton: 'swal-button-custom'
                }
            });
            return false;
        }
    });
</script>

</body>
</html>