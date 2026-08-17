{{--
    First-paint splash for the public app. Rendered as the very first node in
    <body> so it covers the screen before Vite CSS/JS or webfonts arrive, then
    fades itself out on a fixed timeline — never waiting on app state.

    Motion, geometry and palette are the vanilla-CSS translation of the shared
    "Splash" composition (mark-only, no wordmark); the mark is
    app-logo-icon.blade.php verbatim. Everything here is inline and
    self-removing so nothing leaks into the app.
--}}
<div id="app-splash" aria-hidden="true">
    <style>
        /* easings from the composition (easeOutQuart / easeOutBack / easeInOutCubic) */
        #app-splash {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #020617;
            animation: sp-out 0.55s cubic-bezier(0.65, 0, 0.35, 1) 1.55s both;
        }

        /* soft brand glow behind the mark */
        #app-splash .sp-glow {
            position: absolute;
            left: 50%;
            top: 50%;
            width: min(150vw, 150vh, 1000px);
            height: min(150vw, 150vh, 1000px);
            transform: translate(-50%, -50%);
            pointer-events: none;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.38) 0%, rgba(99, 102, 241, 0.16) 32%, rgba(2, 6, 23, 0) 62%);
            animation: sp-glow 0.3s ease-out 0.05s both;
        }

        #app-splash .sp-markwrap {
            position: relative;
            width: clamp(120px, 34vw, 176px);
            height: clamp(120px, 34vw, 176px);
        }

        /* expanding rings, centred on the mark */
        #app-splash .sp-ring {
            position: absolute;
            inset: 0;
            border-radius: 26%;
            border: 2px solid rgba(147, 197, 253, 0.9);
            pointer-events: none;
        }
        #app-splash .sp-ring--1 { animation: sp-ring 0.9s cubic-bezier(0.165, 0.84, 0.44, 1) 0.42s both; }
        #app-splash .sp-ring--2 { animation: sp-ring 0.9s cubic-bezier(0.165, 0.84, 0.44, 1) 0.60s both; }

        #app-splash .sp-mark {
            width: 100%;
            height: 100%;
            overflow: visible;
            transform: scale(0.18) rotate(-16deg);
            animation: sp-tile 0.63s cubic-bezier(0.34, 1.56, 0.64, 1) 0.05s both;
        }

        #app-splash .sp-bubble {
            transform-box: fill-box;
            transform-origin: center;
            transform: scale(0.35);
            opacity: 0;
            animation: sp-bubble 0.38s cubic-bezier(0.34, 1.56, 0.64, 1) 0.28s both;
        }

        #app-splash .sp-bar {
            transform-box: fill-box;
            transform-origin: bottom;
            transform: scaleY(0);
        }
        #app-splash .sp-bar--1 { animation: sp-bar 0.36s cubic-bezier(0.34, 1.56, 0.64, 1) 0.46s both; }
        #app-splash .sp-bar--2 { animation: sp-bar 0.36s cubic-bezier(0.34, 1.56, 0.64, 1) 0.55s both; }
        #app-splash .sp-bar--3 { animation: sp-bar 0.36s cubic-bezier(0.34, 1.56, 0.64, 1) 0.64s both; }

        /* light sweep across the tile, clipped to its rounded shape */
        #app-splash .sp-sweep {
            transform-box: fill-box;
            transform: translateX(-40px);
            animation: sp-sweep 0.45s cubic-bezier(0.65, 0, 0.35, 1) 0.60s both;
        }

        @keyframes sp-out {
            to { opacity: 0; visibility: hidden; }
        }
        @keyframes sp-glow {
            from { opacity: 0; }
            to   { opacity: 0.85; }
        }
        @keyframes sp-ring {
            from { transform: scale(0.55); opacity: 0.5; }
            to   { transform: scale(2.1);  opacity: 0; }
        }
        @keyframes sp-tile {
            to { transform: scale(1) rotate(0deg); }
        }
        @keyframes sp-bubble {
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes sp-bar {
            to { transform: scaleY(1); }
        }
        @keyframes sp-sweep {
            from { transform: translateX(-40px); }
            to   { transform: translateX(90px); }
        }

        @media (prefers-reduced-motion: reduce) {
            #app-splash {
                animation: sp-out 0.4s ease-out 0.9s both;
            }
            #app-splash .sp-glow,
            #app-splash .sp-mark,
            #app-splash .sp-bubble,
            #app-splash .sp-bar {
                animation: none;
                opacity: 1;
                transform: none;
            }
            #app-splash .sp-glow { opacity: 0.85; }
            #app-splash .sp-ring { display: none; }
        }
    </style>

    <div class="sp-glow"></div>

    <div class="sp-markwrap">
        <div class="sp-ring sp-ring--1"></div>
        <div class="sp-ring sp-ring--2"></div>
        <svg class="sp-mark" viewBox="0 0 64 64" role="img" aria-label="تقييم التدريب">
            <defs>
                <linearGradient id="sp-brand-gradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#0EA5E9"/>
                    <stop offset="0.55" stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#6366F1"/>
                </linearGradient>
                <linearGradient id="sp-sweep-gradient" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" stop-color="#fff" stop-opacity="0"/>
                    <stop offset="0.5" stop-color="#fff" stop-opacity="0.55"/>
                    <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                </linearGradient>
                <clipPath id="sp-mark-clip"><rect width="64" height="64" rx="16"/></clipPath>
            </defs>
            <rect width="64" height="64" rx="16" fill="url(#sp-brand-gradient)"/>
            <g class="sp-bubble">
                <path transform="translate(64 0) scale(-1 1)" fill="#FFFFFF" d="M21 13h22a10 10 0 0 1 10 10v13a10 10 0 0 1-10 10H33l-10.5 8.5a1.4 1.4 0 0 1-2.3-1.1V46H21a10 10 0 0 1-10-10V23a10 10 0 0 1 10-10Z"/>
                <rect class="sp-bar sp-bar--1" x="20" y="20" width="6.5" height="20" rx="3.25" fill="#2563EB"/>
                <rect class="sp-bar sp-bar--2" x="28.75" y="26" width="6.5" height="14" rx="3.25" fill="#2563EB" opacity="0.8"/>
                <rect class="sp-bar sp-bar--3" x="37.5" y="30.5" width="6.5" height="9.5" rx="3.25" fill="#2563EB" opacity="0.62"/>
            </g>
            <g clip-path="url(#sp-mark-clip)">
                <g transform="rotate(14 32 32)">
                    <rect class="sp-sweep" x="-4" y="-10" width="26" height="84" fill="url(#sp-sweep-gradient)"/>
                </g>
            </g>
        </svg>
    </div>

    <script>
        (function () {
            var splash = document.getElementById('app-splash');
            if (!splash) {
                return;
            }
            var remove = function () {
                if (splash && splash.parentNode) {
                    splash.parentNode.removeChild(splash);
                }
                splash = null;
            };
            // `window` survives wire:navigate morphs but is fresh on a real
            // document load, so the splash plays once per load and never
            // replays while navigating inside the app.
            if (window.__appSplashShown) {
                remove();
                return;
            }
            window.__appSplashShown = true;
            splash.addEventListener('animationend', function (event) {
                if (event.target === splash && event.animationName === 'sp-out') {
                    remove();
                }
            });
            // safety net: leave regardless of animation events or app state
            setTimeout(remove, 2600);
        })();
    </script>
</div>
