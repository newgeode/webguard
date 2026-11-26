<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <title>Verifying Your Connection</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

        :root {
            color-scheme: dark;
            --accent: #4bb9e8;
            --accent-rgb: 75, 185, 232;
            --accent-soft: rgba(75, 185, 232, .14);
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

        .timer-wrap {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 30px auto 26px
        }

        .timer-ring {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
            overflow: visible
        }

        .timer-ring circle {
            fill: none;
            stroke-width: 5
        }

        .ring-track {
            stroke: #26364c
        }

        .ring-progress {
            stroke: var(--accent);
            stroke-linecap: round;
            stroke-dasharray: 490.09;
            stroke-dashoffset: 0;
            filter: drop-shadow(0 0 6px var(--accent-soft));
            transition: stroke-dashoffset .25s linear
        }

        .timer-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center
        }

        .timer-number {
            font: 800 40px/.95 'Manrope', sans-serif;
            letter-spacing: -.07em;
            font-variant-numeric: tabular-nums
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            color: #9aabc0;
            font-size: 11px;
            margin-bottom: 10px
        }

        .progress-meta strong {
            color: var(--accent);
            font-variant-numeric: tabular-nums
        }

        .progress-track {
            height: 5px;
            border-radius: 9px;
            background: #26364c;
            overflow: hidden
        }

        .progress-fill {
            height: 100%;
            width: 0;
            border-radius: 9px;
            background: linear-gradient(90deg, var(--accent-soft), var(--accent));
            transition: width .25s linear
        }

        .footnote {
            margin: 24px 0 0;
            color: #74869e;
            font-size: 11px;
            line-height: 1.6
        }

        .is-complete .live-dot {
            animation: none
        }

        .is-complete .timer-number {
            color: var(--accent)
        }

        @keyframes pulse {
            50% {
                opacity: .35;
                box-shadow: 0 0 3px var(--accent)
            }
        }

        .timer-number.compact {
            font-size: 25px;
            letter-spacing: -.04em
        }

        @media(max-width:480px) {
            .verify-card {
                padding: 34px 23px 29px;
                border-radius: 19px
            }

            .timer-wrap {
                width: 164px;
                height: 164px
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
            <div class="eyebrow"><span class="live-dot" id="status-dot"></span><span id="status-label">SECURE CONNECTION CHECK</span></div>
            <div class="http-status" role="status">HTTP STATUS <strong>{{$status}}</strong></div>
            <h1 id="page-title">Verifying Connection</h1>
            <div class="timer-wrap" role="timer" aria-live="off" aria-label="Time remaining">
                <svg class="timer-ring" viewBox="0 0 180 180" aria-hidden="true">
                    <circle class="ring-track" cx="90" cy="90" r="78"></circle>
                    <circle class="ring-progress" id="ring-progress" cx="90" cy="90" r="78"></circle>
                </svg>
                <div class="timer-center">
                    <span class="timer-number" id="countdown">00:00</span>
                </div>
            </div>
            <div class="progress-meta">
                <span>Connection check</span><strong id="progress-percent">0%</strong>
            </div>
            <div class="progress-track" aria-hidden="true">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <p class="footnote">Please wait a moment while we redirect you.</p>
        </section>
    </main>
    <script>
        (()=> {

            const duration = '{{$duration}}'; // sec

            let started = Date.now();

            const circumference = 2*Math.PI*78;
            const number = document.getElementById("countdown");
            const ring = document.getElementById("ring-progress");
            const fill = document.getElementById("progress-fill");
            const percent = document.getElementById("progress-percent");
            const title = document.getElementById("page-title");
            const label = document.getElementById("status-label");
            const card = document.querySelector(".verify-card");
            ring.style.strokeDasharray = String(circumference);

            function render(left){
                const elapsed = Math.max(0,Math.min(duration,duration-left));
                const progress = elapsed/duration;
                const whole = Math.max(0,Math.ceil(left));
                const days = Math.floor(whole/86400);
                const hours = Math.floor((whole%86400)/3600);
                const mins = Math.floor((whole%3600)/60);
                const secs = whole%60;
                let display;
                if(days > 0) display=days+":"+String(hours).padStart(2,"0")+":"+String(mins).padStart(2,"0")+":"+String(secs).padStart(2,"0");
                else if(duration >= 3600) display=String(hours).padStart(2,"0")+":"+String(mins).padStart(2,"0")+":"+String(secs).padStart(2,"0");
                else display = String(Math.floor(whole/60)).padStart(2,"0")+":"+String(secs).padStart(2,"0");
                number.textContent = display;
                number.classList.toggle("compact",display.length > 5);
                ring.style.strokeDashoffset = String(circumference*progress);
                fill.style.width = (progress * 100)+"%";
                percent.textContent = Math.round(progress * 100)+"%";
            }

            function complete(){
                card.classList.add("is-complete");
                title.textContent = "Connection Verified";
                setTimeout(() => {
                    document.cookie = "webguard_verified={{$token}}; path=/; max-age=3600; SameSite=Lax";
                    window.location.reload();
                }, 1000);
            }

            function run(){
                const left = Math.max(0,duration-(Date.now()-started)/1000);
                render(left);
                if(left <= 0){ complete(); return; }
                const timer = window.setInterval(()=>{
                    const remaining = Math.max(0,duration-(Date.now()-started)/1000);
                    render(remaining);
                    if(remaining <= 0){ window.clearInterval(timer); complete(); }
                },100);
            }

            run();
        })();
    </script>
</body>

</html>