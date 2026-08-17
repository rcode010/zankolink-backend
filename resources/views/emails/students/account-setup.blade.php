<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>Set Up Your ZankoLine Account</title>

    <style>
        /* Reset */
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #F0F4F8;
        }

        /* Base */
        .wrapper {
            width: 100%;
            background-color: #F0F4F8;
            padding: 40px 0;
        }

        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .header {
            background-color: #1B3A5C;
            padding: 32px 40px;
            text-align: center;
        }

        .header-logo {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .header-logo span {
            color: #4DA3E0;
        }

        .header-subtitle {
            font-size: 12px;
            color: #A8C4DC;
            margin-top: 4px;
            font-family: Arial, sans-serif;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Body */
        .body {
            padding: 40px 40px 32px;
        }

        .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #1B3A5C;
            font-family: Georgia, 'Times New Roman', serif;
            margin: 0 0 12px;
        }

        .intro {
            font-size: 15px;
            color: #4A5568;
            line-height: 1.7;
            font-family: Arial, sans-serif;
            margin: 0 0 28px;
        }

        /* Account Setup Box */
        .setup-box {
            background-color: #F7FAFC;
            border: 1px solid #CBD5E0;
            border-left: 4px solid #1B3A5C;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }

        .setup-title {
            font-size: 13px;
            font-weight: 700;
            color: #2D3748;
            font-family: Arial, sans-serif;
            margin-bottom: 8px;
        }

        .setup-text {
            font-size: 13px;
            color: #4A5568;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
        }

        /* Warning Box */
        .warning-box {
            background-color: #FFFBEB;
            border: 1px solid #F6E05E;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 28px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #744210;
            line-height: 1.6;
        }

        .warning-box strong {
            color: #92400E;
        }

        /* CTA Button */
        .btn-wrap {
            text-align: center;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-block;
            background-color: #1B3A5C;
            color: #ffffff !important;
            text-decoration: none;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 36px;
            border-radius: 6px;
            letter-spacing: 0.3px;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 0 0 24px;
        }

        /* Expiration */
        .expiration {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #718096;
            line-height: 1.6;
            margin: 0 0 12px;
        }

        .expiration strong {
            color: #2D3748;
        }

        /* Security */
        .security {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #718096;
            line-height: 1.6;
            margin: 0;
        }

        /* Footer */
        .footer {
            background-color: #F7FAFC;
            border-top: 1px solid #E2E8F0;
            padding: 20px 40px;
            text-align: center;
        }

        .footer p {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #A0AEC0;
            margin: 4px 0;
            line-height: 1.6;
        }

        .footer a {
            color: #4DA3E0;
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .container {
                border-radius: 0 !important;
            }

            .body {
                padding: 28px 24px !important;
            }

            .header {
                padding: 24px 24px !important;
            }

            .footer {
                padding: 16px 24px !important;
            }
        }
    </style>
</head>

<body>

<div class="wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header">
            <div class="header-logo">
                Zanko<span>Link</span>
            </div>

            <div class="header-subtitle">
                Kurdistan Region Ministry of Higher Education
            </div>
        </div>

        {{-- Body --}}
        <div class="body">

            <p class="greeting">
                Welcome, {{ $studentName }}
            </p>

            <p class="intro">
                Your ZankoLine student account is ready to be activated.
                To complete your account setup, please create your password
                using the button below.
            </p>

            {{-- Account Setup --}}
            <div class="setup-box">

                <div class="setup-title">
                    Complete Your Account Setup
                </div>

                <p class="setup-text">
                    Click the button below to set your password and activate
                    your ZankoLine student account.
                </p>

            </div>

            {{-- CTA --}}
            <div class="btn-wrap">
                <a href="{{ $setupUrl }}" class="btn">
                    Set Up Your Account {{$setupUrl}}
                </a>
            </div>

            {{-- Expiration Warning --}}
            <div class="warning-box">
                ⚠️
                <strong>Important:</strong>
                This setup link will expire on
                <strong>{{ $expiresAt }}</strong>.
                For your security, the link can only be used once.
            </div>

            <hr class="divider"/>

            <p class="expiration">
                <strong>Account setup link:</strong><br>
                This link is unique to your account and should not be
                shared with anyone.
            </p>

            <p class="security">
                If you did not expect this email or believe this account
                was created by mistake, please contact the university
                administration.
            </p>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                This email was sent automatically by the ZankoLine system.
            </p>

            <p>
                If you did not expect this account, please contact
                <a href="mailto:support@zankolink.iq">
                    support@zankolink.iq
                </a>
            </p>

            <p style="margin-top:8px;">
                © {{ date('Y') }} ZankoLine · Kurdistan Region Ministry of Higher Education
            </p>
        </div>

    </div>
</div>

</body>
</html>
