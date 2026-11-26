<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="theme-color" content="#0b1220">
        <title>Access Restricted</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

            :root {
                color-scheme: dark;
                --accent: #f05252;
                --accent-rgb: 240, 82, 82;
                --accent-soft: rgba(240, 82, 82, .14);
                --bg: #0b1220;
                --panel: #111c2e;
                --muted: #91a0b6;
                --text: #f3f7fc;
                --line: #26364c
            }

            * {
                box-sizing: border-box
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: radial-gradient(ellipse at 50% 5%, #1a3150 0%, #0b1220 58%);
                color: var(--text);
                font-family: 'DM Sans', sans-serif;
                display: grid;
                place-items: center;
                padding: 28px 18px
            }

            .page-shell {
                width: min(100%, 520px);
                text-align: center
            }

            .verify-card {
                position: relative;
                overflow: hidden;
                padding: 42px 42px 34px;
                border: 1px solid rgba(145, 170, 202, .17);
                border-radius: 24px;
                background: linear-gradient(145deg, rgba(22, 36, 57, .96), rgba(13, 24, 41, .97));
                box-shadow: 0 28px 90px rgba(0, 0, 0, .38)
            }

            .verify-card:before {
                content: "";
                position: absolute;
                inset: 0 18% auto;
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--accent), transparent);
                opacity: .8
            }

            .brand-mark {
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                margin-bottom: 27px
            }

            .brand-mark span {
                display: block;
                width: 7px;
                border-radius: 8px;
                background: var(--accent);
                box-shadow: 0 0 16px var(--accent-soft)
            }

            .brand-mark span:nth-child(1) {
                height: 15px;
                opacity: .55
            }

            .brand-mark span:nth-child(2) {
                height: 27px
            }

            .brand-mark span:nth-child(3) {
                height: 19px;
                opacity: .75
            }

            .eyebrow {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 9px;
                color: #a8b8cc;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .18em
            }

            .live-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--accent);
                box-shadow: 0 0 12px var(--accent);
                animation: pulse 1.5s infinite
            }

            h1 {
                font: 800 clamp(25px, 5vw, 32px)/1.2 'Manrope', sans-serif;
                letter-spacing: -.045em;
                margin: 17px 0 10px
            }

            .footnote {
                margin: 24px 0 0;
                color: #74869e;
                font-size: 11px;
                line-height: 1.6
            }

            @keyframes pulse {
                50% {
                    opacity: .35;
                    box-shadow: 0 0 3px var(--accent)
                }
            }

        

            @media(max-width:480px) {
                .verify-card {
                    padding: 34px 23px 29px;
                    border-radius: 19px
                }

                h1 {
                    font-size: 26px
                }
            }

            @media(prefers-reduced-motion:reduce) {

                *,
                *::before,
                *::after {
                    animation-duration: .01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: .01ms !important
                }
            }

            .http-status {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                margin: 18px auto 0;
                padding: 8px 13px;
                border: 1px solid rgba(145, 170, 202, .2);
                border-radius: 8px;
                background: rgba(7, 15, 27, .42);
                color: #9aabc0;
                font-size: 9px;
                font-weight: 700;
                letter-spacing: .13em
            }

            .http-status strong {
                color: var(--accent);
                font-weight: bold !important;
            }

            .http-status span {
                color: #a8b8cc
            }

            @media(max-width:480px) {
                .http-status {
                    margin-top: 15px
                }
            }
        </style>
    </head>
    <body>
        <main class="page-shell">
            <section class="verify-card" aria-labelledby="page-title">
                <div class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></div>
                <div class="eyebrow"><span class="live-dot" id="status-dot"></span><span id="status-label">SECURITY NOTICE</span></div>
                <div class="http-status" role="status">HTTP STATUS <strong>{{$status}}</strong></div>
                <h1 id="page-title">Access Restricted</h1>
                <p class="message">{{request()->ip()}}</p>
                <p class="footnote">Access from this IP address is currently restricted.</p>
            </section>
        </main>
    </body>
</html>