<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PreLease Canada</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .email-header { background: #D80621; padding: 24px 32px; text-align: center; }
        .email-header img { height: 40px; }
        .email-header h2 { color: #ffffff; margin: 8px 0 0; font-size: 16px; font-weight: 400; }
        .email-body { padding: 32px; }
        .email-body h1 { font-size: 24px; color: #000; margin: 0 0 16px; }
        .email-body p { font-size: 15px; color: #333; line-height: 1.6; margin: 0 0 12px; }
        .email-body .highlight { color: #D80621; font-weight: 600; }
        .email-btn { display: inline-block; background: #D80621; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 50px; font-weight: 600; font-size: 15px; margin: 16px 0; }
        .email-btn-dark { display: inline-block; background: linear-gradient(#191919, #2b2b2b); color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 50px; font-weight: 600; font-size: 15px; margin: 16px 0; }
        .info-box { background: #fafafa; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin: 16px 0; }
        .info-box .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .info-box .row .label { color: #666; }
        .info-box .row .value { color: #000; font-weight: 600; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #fce4ec; color: #c62828; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-active { background: #e3f2fd; color: #1565c0; }
        .email-footer { background: #fafafa; border-top: 1px solid #eee; padding: 24px 32px; text-align: center; }
        .email-footer p { font-size: 13px; color: #999; margin: 4px 0; }
        .email-footer a { color: #D80621; text-decoration: none; }
        @media screen and (max-width: 600px) {
            .email-body { padding: 20px; }
            .email-body h1 { font-size: 20px; }
            .info-box .row { flex-direction: column; gap: 2px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f5f5; padding: 24px 0;">
        <tr>
            <td align="center">
                <div class="email-wrapper">
                    <div class="email-header">
                        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}">
                            <img src="{{ asset('build/assets/email/logo.webp') }}" alt="PreLease Canada">
                        </a>
                        @hasSection('header-subtitle')
                            <h2>@yield('header-subtitle')</h2>
                        @endif
                    </div>

                    <div class="email-body">
                        @yield('content')
                    </div>

                    <div class="email-footer">
                        <p>&copy; {{ date('Y') }} Prelease Canada. All rights reserved.</p>
                        <p><a href="{{ config('app.frontend_url', 'http://localhost:3000') }}">Visit PreLease Canada</a></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
