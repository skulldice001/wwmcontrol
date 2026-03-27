/**
 * DiceRenderer – realistic 3D dice using Three.js
 *
 * Each die is a BoxGeometry mesh with per-face canvas textures (dot layouts).
 * Rolling = continuous angular velocity per frame.
 * Landing = velocity damping phase → smooth slerp to exact target quaternion.
 */
import * as THREE from 'three';

// ── Dot positions per face value (normalised 0-1 x,y) ──────────────────────
const DOTS = {
    1: [[0.50, 0.50]],
    2: [[0.28, 0.72], [0.72, 0.28]],
    3: [[0.28, 0.72], [0.50, 0.50], [0.72, 0.28]],
    4: [[0.28, 0.28], [0.72, 0.28], [0.28, 0.72], [0.72, 0.72]],
    5: [[0.28, 0.28], [0.72, 0.28], [0.50, 0.50], [0.28, 0.72], [0.72, 0.72]],
    6: [[0.28, 0.22], [0.72, 0.22], [0.28, 0.50], [0.72, 0.50], [0.28, 0.78], [0.72, 0.78]],
};

// Three.js BoxGeometry material index → die face value
// indices: 0=+X(right), 1=-X(left), 2=+Y(top), 3=-Y(bottom), 4=+Z(front), 5=-Z(back)
// Opposite pairs: 1↔6, 2↔5, 3↔4
const GEOM_IDX_TO_FACE = [2, 5, 3, 4, 1, 6];

// Euler rotations to present each face value toward the camera (+Z)
const FACE_EULER = {
    1: new THREE.Euler( 0,           0,  0),   // +Z already faces camera
    2: new THREE.Euler( 0,  -Math.PI/2,  0),   // +X → +Z
    3: new THREE.Euler( Math.PI/2,   0,  0),   // +Y → +Z
    4: new THREE.Euler(-Math.PI/2,   0,  0),   // -Y → +Z
    5: new THREE.Euler( 0,   Math.PI/2,  0),   // -X → +Z
    6: new THREE.Euler( 0,   Math.PI,    0),   // -Z → +Z
};

// ── Canvas texture drawing ──────────────────────────────────────────────────
function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r);
    ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
    ctx.lineTo(x + r, y + h);
    ctx.arcTo(x, y + h, x, y + h - r, r);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r);
    ctx.closePath();
}

function makeFaceTexture(value) {
    const S = 256;
    const c = document.createElement('canvas');
    c.width = c.height = S;
    const ctx = c.getContext('2d');

    // Background gradient
    const bg = ctx.createLinearGradient(0, 0, S, S);
    bg.addColorStop(0, '#fdf6e3');
    bg.addColorStop(1, '#ede0c4');
    ctx.fillStyle = bg;
    roundRect(ctx, 6, 6, S - 12, S - 12, 28);
    ctx.fill();

    // Inner highlight
    const hi = ctx.createLinearGradient(0, 0, 0, S * 0.5);
    hi.addColorStop(0, 'rgba(255,255,255,0.55)');
    hi.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = hi;
    roundRect(ctx, 6, 6, S - 12, S - 12, 28);
    ctx.fill();

    // Dots
    const r = S * 0.09;
    ctx.fillStyle = value === 1 ? '#c0392b' : '#1a1a2e';
    for (const [nx, ny] of DOTS[value]) {
        const x = nx * S, y = ny * S;
        ctx.save();
        ctx.shadowColor = 'rgba(0,0,0,0.35)';
        ctx.shadowBlur = 6;
        ctx.shadowOffsetY = 2;
        ctx.beginPath();
        ctx.arc(x, y, r, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
        // Dot highlight
        ctx.fillStyle = 'rgba(255,255,255,0.18)';
        ctx.beginPath();
        ctx.arc(x - r * 0.28, y - r * 0.28, r * 0.45, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = value === 1 ? '#c0392b' : '#1a1a2e';
    }

    return new THREE.CanvasTexture(c);
}

// ── Easing ──────────────────────────────────────────────────────────────────
function easeOutBack(t) {
    const c1 = 1.4, c3 = c1 + 1;
    return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
}

// ── DiceRenderer class ──────────────────────────────────────────────────────
export class DiceRenderer {
    constructor(canvas, width, height) {
        this._canvas  = canvas;
        this._width   = width;
        this._height  = height;
        this._running = false;
        this._rafId   = null;
        this._timers  = [];

        // Renderer
        this._renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        this._renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this._renderer.setSize(width, height);

        // Scene
        this._scene = new THREE.Scene();

        // Camera
        this._camera = new THREE.PerspectiveCamera(42, width / height, 0.1, 100);
        this._camera.position.set(0, 0.6, 9);
        this._camera.lookAt(0, 0, 0);

        // Lighting
        this._scene.add(new THREE.AmbientLight(0xffffff, 0.55));
        const sun = new THREE.DirectionalLight(0xffffff, 0.85);
        sun.position.set(4, 8, 6);
        this._scene.add(sun);
        const fill = new THREE.DirectionalLight(0xfff4e0, 0.25);
        fill.position.set(-4, -2, 4);
        this._scene.add(fill);

        // Pre-build textures
        this._textures = {};
        for (let v = 1; v <= 6; v++) this._textures[v] = makeFaceTexture(v);

        // Create 3 dice
        const spacing = 2.6;
        this._dice = [-spacing, 0, spacing].map((x, i) => this._createDie(x, i));

        this._startLoop();
    }

    _createDie(xPos, idx) {
        const mats = GEOM_IDX_TO_FACE.map(v =>
            new THREE.MeshPhongMaterial({
                map: this._textures[v],
                shininess: 80,
                specular: new THREE.Color(0xffffff),
            })
        );
        const geo  = new THREE.BoxGeometry(1.5, 1.5, 1.5);
        const mesh = new THREE.Mesh(geo, mats);
        mesh.position.set(xPos, 0, 0);
        // Stagger initial rotations so dice don't look identical
        mesh.rotation.set(
            Math.random() * Math.PI * 2,
            Math.random() * Math.PI * 2,
            Math.random() * Math.PI * 2,
        );
        this._scene.add(mesh);

        return {
            mesh,
            angVel: new THREE.Vector3(),  // radians/frame
            mode: 'static',               // 'static' | 'spinning' | 'landing'
        };
    }

    // ── Public API ─────────────────────────────────────────────────────────

    /** Show static dice – no movement */
    setStatic() {
        this._cancelTimers();
        for (const d of this._dice) {
            d.mode   = 'static';
            d.angVel.set(0, 0, 0);
        }
    }

    /** Start free spinning (pre-roll) */
    startRolling() {
        this._cancelTimers();
        this._dice.forEach((d, i) => {
            d.mode = 'spinning';
            // Each die a slightly different axis/speed
            const base = 0.055 + i * 0.01;
            d.angVel.set(
                (Math.random() - 0.5) * base * 2.5,
                (Math.random() > 0.5 ? 1 : -1) * (base + Math.random() * base),
                (Math.random() - 0.5) * base,
            );
        });
    }

    /**
     * Land on specific face values with staggered animation.
     * @param {number[]} values – array of 3 values [1-6]
     * @param {Function} onDone – callback when all dice settle
     */
    land(values, onDone) {
        this._cancelTimers();
        values.forEach((v, i) => {
            const t = setTimeout(() => {
                this._landDie(this._dice[i], v, i === 2 ? onDone : null);
            }, i * 180);
            this._timers.push(t);
        });
    }

    resize(w, h) {
        this._width = w; this._height = h;
        this._renderer.setSize(w, h);
        this._camera.aspect = w / h;
        this._camera.updateProjectionMatrix();
    }

    destroy() {
        this._running = false;
        if (this._rafId) cancelAnimationFrame(this._rafId);
        this._cancelTimers();
        this._renderer.dispose();
    }

    // ── Internal ───────────────────────────────────────────────────────────

    _startLoop() {
        this._running = true;
        const tick = () => {
            if (!this._running) return;
            this._rafId = requestAnimationFrame(tick);
            this._tick();
            this._renderer.render(this._scene, this._camera);
        };
        tick();
    }

    _tick() {
        for (const d of this._dice) {
            if (d.mode === 'spinning') {
                d.mesh.rotation.x += d.angVel.x;
                d.mesh.rotation.y += d.angVel.y;
                d.mesh.rotation.z += d.angVel.z;
            }
        }
    }

    _landDie(die, value, onDone) {
        die.mode = 'landing';

        const targetQuat = new THREE.Quaternion().setFromEuler(FACE_EULER[value]);

        // Capture spin axis/speed as quaternion parameters (avoids gimbal lock in Phase 1)
        const angSpeed = die.angVel.length();
        const angAxis  = angSpeed > 0.0001
            ? die.angVel.clone().normalize()
            : new THREE.Vector3(0, 1, 0);

        // Duration: 55% spin-down, 45% settle to exact face
        const totalMs   = 1200;
        const splitAt   = 0.55;
        const startTime = performance.now();

        let phase2StartQuat = null;

        const animate = (now) => {
            if (die.mode !== 'landing') return; // cancelled

            const t = Math.min((now - startTime) / totalMs, 1);

            if (t < splitAt) {
                // Phase 1 – quaternion-based spin-down (no gimbal lock)
                const p    = t / splitAt;             // 0→1 within phase 1
                const damp = Math.pow(1 - p, 1.6);   // velocity envelope
                const step = angSpeed * damp;
                if (step > 0.0001) {
                    const delta = new THREE.Quaternion().setFromAxisAngle(angAxis, step);
                    die.mesh.quaternion.premultiply(delta);
                }
            } else {
                // Phase 2 – slerp to target; allow natural easeOutBack overshoot
                if (!phase2StartQuat) {
                    phase2StartQuat = die.mesh.quaternion.clone();
                }
                const p     = (t - splitAt) / (1 - splitAt); // 0→1 within phase 2
                const eased = easeOutBack(p);                 // natural bounce, no clamp
                die.mesh.quaternion.slerpQuaternions(phase2StartQuat, targetQuat, eased);
            }

            if (t < 1) {
                requestAnimationFrame(animate);
            } else {
                die.mesh.quaternion.copy(targetQuat); // guarantee exact face
                die.angVel.set(0, 0, 0);
                die.mode = 'static';
                if (onDone) onDone();
            }
        };

        requestAnimationFrame(animate);
    }

    _cancelTimers() {
        this._timers.forEach(clearTimeout);
        this._timers = [];
    }
}
