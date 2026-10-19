@props ([
    'id' => 'room-bg',
    'minHeight' => 'clamp(34rem,80vh,48rem)',
    'theta' => 0, // degrees: which wall you face (try 0, 90, 180, -90)
    'phi' => 85, // degrees: 90 = eye level
    'radius' => 0.3, // camera distance, as a fraction of the model's largest size
    'fov' => 85, // field of view in degrees
    'pan' => 20, // loop: degrees swung left and right
    'tilt' => 3, // loop: degrees up and down
    'dolly' => 0.25, // loop: glides this fraction closer, then back out
    'seconds' => 16, // loop: seconds per full loop
    'scrim' => 0.6 // darkness behind the content (0 to 1)
])

@php
    $modelPath = 'models/smoking-room-web.glb';
    $modelUrl =
        asset($modelPath) .
        '?v=' .
        (file_exists(public_path($modelPath)) ? filemtime(public_path($modelPath)) : '1');

    $cfg = [
        'theta' => $theta,
        'phi' => $phi,
        'radius' => $radius,
        'fov' => $fov,
        'target' => [0.5, 0.35, 0.5],
        'loop' => ['pan' => $pan, 'tilt' => $tilt, 'dolly' => $dolly, 'seconds' => $seconds],
    ];
@endphp

<section
    {{ $attributes }}
    style="position:relative;isolation:isolate;overflow:hidden;background:#0a0a0a;min-height:{{ $minHeight }}"
>
    @once
        {{-- Start downloading the model right away; the browser keeps it in its cache afterwards --}}
        <link rel="preload" href="{{ $modelUrl }}" as="fetch" crossorigin="anonymous" />
        <script
            type="module"
            src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"
        ></script>
    @endonce

    <model-viewer
        id="{{ $id }}"
        src="{{ $modelUrl }}"
        alt="3D scene of an abandoned room reclaimed by nature"
        disable-zoom
        interaction-prompt="none"
        interpolation-decay="120"
        camera-orbit="0deg 85deg 1m"
        field-of-view="{{ $fov }}deg"
        environment-image="neutral"
        exposure="1.6"
        shadow-intensity="0"
        loading="eager"
        reveal="auto"
        style="
            position: absolute;
            inset: 0;
            z-index: 0;
            width: 100%;
            height: 100%;
            --poster-color: transparent;
            background: transparent;
        "
    ></model-viewer>

    {{-- Scrim: keeps the content readable on top of the scene --}}
    <div
        aria-hidden="true"
        style="position:absolute;inset:0;z-index:1;pointer-events:none;background:radial-gradient(ellipse at center,rgba(0,0,0,{{ $scrim }}),rgba(0,0,0,{{ max($scrim - 0.35, 0.1) }}) 65%,rgba(0,0,0,0.1) 100%)"
    ></div>

    {{-- Content, centered on both axes --}}
    <div
        style="
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: inherit;
            padding: 4rem 1rem;
        "
    >
        {{ $slot }}
    </div>

    <script>
        (function () {
            var mv = document.getElementById(@json ($id));
            if (!mv) return;
            var hero = mv.parentElement;
            var cfg = @json ($cfg);

            var debug = location.search.indexOf('cam') !== -1;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var base = { theta: cfg.theta, phi: cfg.phi, r: 1 };
            var ready = false;

            function place() {
                var c = mv.getBoundingBoxCenter();
                var d = mv.getDimensions();
                var size = Math.max(d.x, d.y, d.z);
                base = { theta: cfg.theta, phi: cfg.phi, r: size * cfg.radius };

                mv.cameraTarget =
                    c.x +
                    (cfg.target[0] - 0.5) * d.x +
                    'm ' +
                    (c.y + (cfg.target[1] - 0.5) * d.y) +
                    'm ' +
                    (c.z + (cfg.target[2] - 0.5) * d.z) +
                    'm';
                mv.fieldOfView = cfg.fov + 'deg';
                mv.cameraOrbit = base.theta + 'deg ' + base.phi + 'deg ' + base.r + 'm';

                if (!debug) {
                    mv.minCameraOrbit =
                        base.theta - 40 + 'deg ' + (base.phi - 20) + 'deg ' + base.r * 0.4 + 'm';
                    mv.maxCameraOrbit = base.theta + 40 + 'deg ' + (base.phi + 20) + 'deg ' + base.r + 'm';
                }
                mv.jumpCameraToGoal();
                ready = true;
            }
            mv.addEventListener('load', place);

            if (debug) {
                mv.setAttribute('camera-controls', '');
                mv.removeAttribute('disable-zoom');
                mv.addEventListener('camera-change', function () {
                    var o = mv.getCameraOrbit(),
                        t = mv.getCameraTarget();
                    var deg = function (x) {
                        return ((x * 180) / Math.PI).toFixed(1);
                    };
                    console.log(
                        "orbit: '" +
                            deg(o.theta) +
                            'deg ' +
                            deg(o.phi) +
                            'deg ' +
                            o.radius.toFixed(3) +
                            "m'",
                        " target: '" +
                            t.x.toFixed(3) +
                            'm ' +
                            t.y.toFixed(3) +
                            'm ' +
                            t.z.toFixed(3) +
                            "m'",
                    );
                });
                return;
            }
            if (reduce) return;

            var visible = true;
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (e) {
                    visible = e[0].isIntersecting;
                }).observe(hero);
            }

            var L = cfg.loop;
            function tick(now) {
                requestAnimationFrame(tick);
                if (!ready || !visible) return;
                var angle = (now / 1000 / L.seconds) * Math.PI * 2;
                var theta = base.theta + Math.sin(angle) * L.pan;
                var phi = base.phi + Math.sin(angle * 2) * L.tilt;
                var r = base.r * (1 - ((1 - Math.cos(angle)) / 2) * L.dolly);
                mv.cameraOrbit = theta.toFixed(2) + 'deg ' + phi.toFixed(2) + 'deg ' + r.toFixed(4) + 'm';
            }
            requestAnimationFrame(tick);
        })();
    </script>
</section>
