<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('email_title', 'Rising Green Energy')</title>
    <style type="text/css">
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            background-color: #f3f4f6;
            color: #1f2937;
        }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #4f46e5; text-decoration: none; }

        /* Outer wrapper */
        .email-outer {
            width: 100%;
            padding: 40px 10px;
            background-color: #f3f4f6;
        }

        /* Main container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Header & Footer (CRM Theme) */
        .email-header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 30px 30px;
        }
        .email-footer {
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 30px 6px 30px;
            text-align: center;
        }
        
        .header-top {
            display: table;
            width: 100%;
        }
        .header-top-left, .header-top-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-top-left { text-align: left; width: 60%; }
        .header-top-right { text-align: right; width: 40%; }
        
        .logo-img {
            max-width: 180px;
            max-height: 60px;
            object-fit: contain;
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 20px;
        }

        /* Body */
        .email-body {
            padding: 30px;
            text-align: left;
        }
        
        .email-body p {
            font-size: 14px;
            margin-bottom: 16px;
            color: #374151;
        }

        /* Beautiful Content Box (Row/Column layout) */
        .content-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 24px 0;
            overflow: hidden;
        }
        .content-box-title {
            padding: 16px 20px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #e5e7eb;
            background-color: rgba(15, 23, 42, 0.05);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            display: table-cell;
            width: 35%;
            padding: 16px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
            vertical-align: middle;
        }
        .info-value {
            display: table-cell;
            width: 65%;
            padding: 16px 20px 16px 0;
            font-size: 14px;
            color: #111827;
            vertical-align: middle;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background-color: #e0e7ff;
            color: #3730a3;
        }

        /* CTA Button */
        .btn-wrap { margin: 30px 0 10px 0; text-align: center; }
        .btn-primary {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none !important;
        }

        /* Footer */
        .email-footer {
            text-align: center;
            padding: 12px 30px 6px 30px;
        }
        .footer-tagline {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #c7d2fe;
        }
        .footer-links { margin: 6px 0; }
        .footer-links a {
            color: #ffffff;
            font-size: 13px;
            margin: 0 10px;
            text-decoration: none;
            opacity: 0.9;
        }
        .footer-disclaimer {
            font-size: 12px;
            opacity: 0.6;
            margin-top: 8px;
            color: #ffffff;
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body, .email-outer { background-color: #111827 !important; color: #f9fafb !important; }
            .email-container { background-color: #1f2937 !important; box-shadow: none !important; border: 1px solid #374151 !important; }
            
            /* Light aesthetic for Header/Footer in Dark Mode */
            .email-header, .email-footer { background-color: #f1f5f9 !important; border-bottom: none !important; border-top: none !important; }
            .header-title, .footer-tagline { color: #1e293b !important; }
            .footer-links a, .footer-disclaimer { color: #475569 !important; opacity: 1 !important; }
            
            .email-body p { color: #d1d5db !important; }
            
            .content-box { background-color: #111827 !important; border-color: #374151 !important; }
            .content-box-title { border-color: #374151 !important; background-color: rgba(79, 70, 229, 0.1) !important; color: #818cf8 !important; }
            .info-row { border-color: #374151 !important; }
            .info-label { color: #9ca3af !important; }
            .info-value { color: #f3f4f6 !important; }
            
            .badge { background-color: rgba(79, 70, 229, 0.2) !important; color: #c7d2fe !important; }
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-outer { padding: 10px 4px !important; }
            .email-header, .email-body, .email-footer { padding: 24px 20px !important; }
            .header-top-left { width: 100%; text-align: center; display: block; }
            .header-top-right { display: none; }
            .info-label { width: 40%; padding: 12px 10px 12px 16px; }
            .info-value { width: 60%; padding: 12px 16px 12px 0; }
        }
    </style>
</head>
<body>

<div class="email-outer">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <div class="email-container">
                    
                    {{-- ====== HEADER ====== --}}
                    <div class="email-header">
                        <div class="header-top">
                            <div class="header-top-left">
                                @php
                                    $companyLogoUrl = 'https://rising-green-energy-crm.fableadtech.in/public/images/template/company-logo-image%20(1).png';
                                @endphp
                                <img src="{{ $companyLogoUrl }}" alt="Rising Green Energy" class="logo-img">
                            </div>
                            <div class="header-top-right">
                                <div style="font-size: 13px; opacity: 0.8;">Automated Notification</div>
                            </div>
                        </div>
                        
                        <div class="header-title">@yield('header_title')</div>
                    </div>

                    {{-- ====== BODY ====== --}}
                    <div class="email-body">
                        @yield('email_body')
                    </div>

                    {{-- ====== FOOTER ====== --}}
                    <div class="email-footer">
                        <div class="footer-tagline">Solar · Sustainable · Clean Energy</div>
                        @php
                            $footerEmail = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'email')->value('value');
                        @endphp
                        @if($footerEmail)
                        <div class="footer-links">
                            <a href="mailto:{{ $footerEmail }}">✉️ {{ $footerEmail }}</a>
                        </div>
                        @endif
                        <div class="footer-disclaimer">
                            © {{ date('Y') }} <a href="https://www.fableadtechnolabs.com/" target="_blank" rel="noopener noreferrer">Fablead Developers Technolab</a>. All rights reserved.
                        </div>
                        <!-- Prevent Gmail Trimming -->
                        <div style="display:none; white-space:nowrap; font:15px courier; line-height:0;">
                            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
                            &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                        </div>
                        <span style="opacity: 0; font-size: 0px; color: transparent;">{{ uniqid() }}</span>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
