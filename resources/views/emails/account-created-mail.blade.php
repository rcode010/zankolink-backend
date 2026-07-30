<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>Your ZankoLink Account</title>
    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #F0F4F8; }

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
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
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

        /* Credentials Box */
        .credentials-box {
            background-color: #F7FAFC;
            border: 1px solid #CBD5E0;
            border-left: 4px solid #1B3A5C;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }
        .credentials-label {
            font-size: 11px;
            color: #718096;
            font-family: Arial, sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .credentials-row {
            display: flex;
            margin-bottom: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .credentials-key {
            color: #718096;
            width: 90px;
            flex-shrink: 0;
        }
        .credentials-value {
            color: #1A202C;
            font-weight: 600;
            word-break: break-all;
        }
        .password-value {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            color: #1B3A5C;
            background-color: #EBF4FF;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 1px;
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

        /* Steps */
        .steps-title {
            font-size: 13px;
            font-weight: 700;
            color: #2D3748;
            font-family: Arial, sans-serif;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #4A5568;
            line-height: 1.5;
        }
        .step-num {
            background-color: #1B3A5C;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 10px;
            margin-top: 1px;
            text-align: center;
            line-height: 20px;
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
            .container { border-radius: 0 !important; }
            .body { padding: 28px 24px !important; }
            .header { padding: 24px 24px !important; }
            .footer { padding: 16px 24px !important; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header">
            <div class="header-logo">Zanko<span>Link</span></div>
            <div class="header-subtitle">Kurdistan Region Ministry of Higher Education</div>
        </div>

        {{-- Body --}}
        <div class="body">

            <p class="greeting">Welcome, {{ $name }}</p>

            <p class="intro">
                Your account on the ZankoLink platform has been created by the system administrator.
                Below are your login credentials. Please keep them confidential and change your password immediately after your first login.
            </p>

            {{-- Credentials --}}
            <div class="credentials-box">
                <div class="credentials-label">Your Login Credentials</div>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-family: Arial, sans-serif; font-size:14px; color:#718096; width:90px; padding-bottom:10px;">Email</td>
                        <td style="font-family: Arial, sans-serif; font-size:14px; color:#1A202C; font-weight:600; padding-bottom:10px;">{{ $email }}</td>
                    </tr>
                    <tr>
                        <td style="font-family: Arial, sans-serif; font-size:14px; color:#718096; width:90px;">Password</td>
                        <td>
                            <span style="font-family:'Courier New',Courier,monospace; font-size:16px; color:#1B3A5C; background-color:#EBF4FF; padding:3px 10px; border-radius:4px; letter-spacing:1px; font-weight:700;">
                                {{ $temporaryPassword }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Warning --}}
            <div class="warning-box">
                ⚠️ <strong>Security Notice:</strong> This is a temporary password generated by the system.
                You are required to change it immediately after your first login. Do not share this email with anyone.
            </div>

            {{-- CTA --}}
            <div class="btn-wrap">
                <a href="https://zanko-book.vercel.app/login" class="btn">Login to ZankoLink</a>
            </div>

            <hr class="divider"/>

            {{-- Steps --}}
            <div class="steps-title">How to change your password</div>

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-bottom:10px;">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:28px; vertical-align:top; padding-top:1px;">
                                    <div style="background-color:#1B3A5C;color:#fff;font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;text-align:center;line-height:20px;font-family:Arial,sans-serif;">1</div>
                                </td>
                                <td style="font-family:Arial,sans-serif;font-size:13px;color:#4A5568;line-height:1.5;">
                                    Log in using your email and the temporary password above.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:10px;">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:28px; vertical-align:top; padding-top:1px;">
                                    <div style="background-color:#1B3A5C;color:#fff;font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;text-align:center;line-height:20px;font-family:Arial,sans-serif;">2</div>
                                </td>
                                <td style="font-family:Arial,sans-serif;font-size:13px;color:#4A5568;line-height:1.5;">
                                    Go to <strong>Settings → Security</strong> from your profile menu.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:28px; vertical-align:top; padding-top:1px;">
                                    <div style="background-color:#1B3A5C;color:#fff;font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;text-align:center;line-height:20px;font-family:Arial,sans-serif;">3</div>
                                </td>
                                <td style="font-family:Arial,sans-serif;font-size:13px;color:#4A5568;line-height:1.5;">
                                    Enter your new password and confirm. Your account will then be fully secured.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>This email was sent automatically by the ZankoLink system.</p>
            <p>If you did not expect this account, please contact <a href="mailto:support@zankolink.iq">support@zankolink.iq</a></p>
            <p style="margin-top:8px;">© {{ date('Y') }} ZankoLink · Kurdistan Region Ministry of Higher Education</p>
        </div>

    </div>
</div>
</body>
</html>
