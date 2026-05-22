<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ \App\Models\LoginSetting::first()->portal_name ?? 'K-Maint' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        @font-face { font-family: "Poppins"; src: url("{{ asset('fonts/Poppins/Poppins-Regular.ttf') }}"); }
        
        :root {
            --avian-primary: #1e7e34; /* Hijau Avian kawan */
            --avian-secondary: #092d20;
        }

        body.login-page {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .login-box { width: 360px; z-index: 2; }
        
        .login-logo { text-align: center; margin-bottom: 20px; }
        .login-logo img { width: 200px; height: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }

        .card { border: none; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; }
        .login-card-body { padding: 30px; background: #fff; }
        .login-box-msg { margin: 0; text-align: center; padding: 0 20px 20px; font-weight: 600; color: #666; }

        .input-group-text { background-color: #f8f9fa; border-left: none; color: #999; cursor: pointer; }
        .form-control { border-right: none; padding: 10px 15px; }
        .form-control:focus { border-color: var(--avian-primary); box-shadow: none; }
        .form-control:focus + .input-group-text { border-color: var(--avian-primary); }

        .btn-avian { 
            background-color: var(--avian-primary); 
            color: white; 
            border: none; 
            padding: 10px; 
            font-weight: bold; 
            transition: 0.3s;
        }
        .btn-avian:hover { background-color: var(--avian-secondary); color: #fff; transform: translateY(-1px); }

        .background-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.3); z-index: 1;
        }

        .quote-footer {
            position: fixed; bottom: 20px; width: 100%; text-align: center;
            color: white; z-index: 2; font-size: 0.85rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
    </style>
</head>

@php
    $settings = \App\Models\LoginSetting::first();
    $bgStyle = $settings && $settings->background_image && file_exists(public_path('login-backgrounds/' . $settings->background_image))
               ? "background-image: url('" . asset('login-backgrounds/' . $settings->background_image) . "');"
               : "background-color: " . ($settings->background_color ?? '#092d20') . ";";
@endphp

<body class="login-page" style="{{ $bgStyle }}">
    <div class="background-overlay"></div>

    <div class="login-box">
        <div class="login-logo">
            <img src="{{ asset('images/avian-logo-normal.png') }}" alt="Logo">
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">KASAKATA MAINTENANCE SYSTEM</p>

                {{-- Alert Error --}}
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="fas fa-exclamation-circle me-1"></i> ID atau Password salah kawan.
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="username" class="form-control" placeholder="ID User / Username" value="{{ old('username') }}" required autofocus>
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-text" onclick="togglePassword()">
                            <span class="fas fa-eye" id="password-icon"></span>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-7">
                            <div class="form-check small">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-avian w-100">LOGIN</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="quote-footer">
        @if($settings && $settings->show_quote)
            <div class="fst-italic">"{{ $settings->quote_text }}"</div>
        @endif
        <div class="mt-1">© {{ date('Y') }} {{ $settings->company_name ?? 'Kasakata Kimia' }}</div>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>