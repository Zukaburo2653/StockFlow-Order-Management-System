<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — StockFlow</title>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --bg: #0D0F14;
            --surface: #13161D;
            --card: #181C25;
            --border: #252A36;
            --accent: #6C8EF5;
            --accent-dim: #3D5199;
            --accent-glow: rgba(108, 142, 245, .15);
            --green: #3DD68C;
            --red: #F05252;
            --slate: #8892A4;
            --text: #E8EBF2;
            --text-mid: #A8B2C4
        }

        html {
            font-size: 14px
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        input {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            font-family: 'DM Sans', system-ui, sans-serif;
            font-size: 13px;
            border-radius: 8px;
            padding: 10px 14px;
            outline: none;
            width: 100%;
            transition: border-color .15s, box-shadow .15s
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow)
        }

        input::placeholder {
            color: var(--slate)
        }

        label {
            font-size: 12px;
            color: var(--slate);
            display: block;
            margin-bottom: 5px
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            font-family: 'DM Sans', system-ui, sans-serif;
            border: none;
            cursor: pointer;
            transition: all .15s;
            width: 100%
        }

        .btn-primary {
            background: var(--accent);
            color: #fff
        }

        .btn-primary:hover {
            background: #7d9cf7
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .alert-error {
            background: rgba(240, 82, 82, .1);
            color: var(--red);
            border: 1px solid rgba(240, 82, 82, .25)
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .fade-in {
            animation: fadeIn .35s ease forwards
        }

        /* Decorative bg dots */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 800px 600px at 30% 20%, rgba(108, 142, 245, .06) 0%, transparent 60%), radial-gradient(ellipse 600px 400px at 80% 80%, rgba(61, 214, 140, .04) 0%, transparent 60%);
            pointer-events: none
        }
    </style>
</head>

<body>
    <div class="card fade-in" style="width:100%;max-width:380px;overflow:hidden;">

        {{-- Header --}}
        <div style="padding:32px 32px 24px;border-bottom:1px solid var(--border);text-align:center;">
            <div
                style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent-dim));display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin:0 auto 14px;">
                ⬡</div>
            <div style="font-family:'DM Serif Display',Georgia,serif;font-size:26px;margin-bottom:4px;">StockFlow</div>
            <div style="font-size:12px;color:var(--slate);">Order Management System</div>
        </div>

        {{-- Form --}}
        <div style="padding:28px 32px;">

            @if(session('error'))
            <div class="alert alert-error">✕ {{ session('error') }}</div>
            @endif
            @if($errors->any())
            <div class="alert alert-error">✕ {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div style="margin-bottom:16px;">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com"
                        autofocus required />
                </div>

                <div style="margin-bottom:24px;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required />
                </div>

                <button type="submit" class="btn btn-primary">→ Sign In</button>
            </form>

            <div style="text-align:center;margin-top:20px;font-size:12px;color:var(--slate);">
                Test credentials: <span style="color:var(--text-mid);">admin@example.com</span> / <span
                    style="color:var(--text-mid);">password</span>
            </div>
        </div>
    </div>
</body>

</html>