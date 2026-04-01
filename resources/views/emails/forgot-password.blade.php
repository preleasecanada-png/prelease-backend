<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Forgot Password</title>
    <style>
        .verify_btn {
            background: #D80621;
            color: #fff;
            font-weight: 600;
            padding: 13px 14px;
            text-decoration: none;
            border-radius: 10px;
            display: inline-block;
            margin: 8px 0;
        }
        @media screen and (max-width: 475px) {
         .verify_btn {
                font-size: 14px;
                padding: 12px;
                border-radius: 10px;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body style="font-family: Arial; background:#f5f5f5; padding:20px;">
    <div style="background:#fff; padding:20px; border-radius:5px;">
        <div style="text-align:center;">
        <img src="{{ asset('public/fav.png') }}" alt="Logo" width="80px">
        </div>
        <h2>Hello {{ $user->first_name ?? 'User' }}</h2>

        <p>You requested to reset your password.</p>

            <a href="{{ $resetLink }}"
               class="verify_btn">
                Reset Password
            </a>

        <p>This link will expire in 30 minutes.</p>

        <p>If you didn’t request this, please ignore this email.</p>

        <br>
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
