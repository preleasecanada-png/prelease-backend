<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PreLease Verify Email</title>
    <style>
        body {
            margin: 0px;
            padding: 0px;
            overflow: hidden;
            font-family: sans-serif;
        }

        .main_body {
            width: 100%;
            height: 70vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: auto;
        }

        .email_varify_back {
            display: flex;
            justify-content: center;
            align-content: center;
            margin: auto;
            margin-top: 20px;
        }

        .email_varify_logo {
            display: flex;
            justify-content: center;
            align-content: center;
            margin: auto;
            margin-top: -100px;
        }

        .email_varify_back img {
            height: 200px;
            min-height: 120px;
            width: 100%;
            max-width: 450px;
        }

        .email_varify_logo img {
            height: 150px;
            min-height: 120px;
            width: 100%;
            max-width: 300px;
        }

        .verify_btn {
            background: #D80621;
            color: #fff;
            font-weight: 600;
            padding: 14px;
            text-decoration: none;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .para {
            color: #000;
            font-size: 20px;
            text-wrap: wrap !important;
            text-align: center;
            padding: 0 10px;
        }

        @media screen and (max-width: 475px) {
            .email_varify_back img {
                height: 150px;
            }

            .email_varify_logo img {
                height: 100px;
            }

            h1 {
                font-size: 24px;
                margin: 20px 0;
            }

            .para {
                font-size: 16px;
                margin: 15px 0;
            }

            .verify_btn {
                font-size: 14px;
                padding: 12px;
                border-radius: 10px;
                margin: 20px 0;
            }

            .email_varify_logo img {
                max-width: 200px;
            }

            .main_body {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body main_body">
        <tr>
            <td class="container">
                <div class="content">
                    <table role="presentation" class="main">
                        <tr>
                            <td class="wrapper">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="align-center email_varify_back" width="100%">
                                            <a href="{{ config('app.frontend_url') }}"><img
                                                    src="{{ asset('build/assets/email/verify_back.webp') }}"
                                                    alt="PreLease"></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="align-center email_varify_logo" width="100%">
                                            <a href="{{ config('app.frontend_url') }}"><img
                                                    src="{{ asset('build/assets/email/logo.webp') }}"
                                                    alt="PreLease"></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h1 align="center">Thank you, {{ $user->name ?? null }}</h1>
                                            <p align="center" class="para">Thank you for registering with us. Please
                                                verify your email address by clicking the button below:</p>

                                            <table align="center" role="presentation" border="0" cellpadding="0"
                                                cellspacing="0">
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <a class="verify_btn" href="{{ $url ?? null }}">Verify
                                                                Email</a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p align="center" class="para">If you did not create an account, no
                                                further action is required.
                                                © 2024 Prelease. All rights reserved.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
