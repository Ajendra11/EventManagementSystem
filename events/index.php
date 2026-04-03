<?php
require_once __DIR__ . '/../includes/layout.php';

$user = auth_user();
render_header('Home');
?>

<!-- ── Hero ─────────────────────────────────────────────────────────── -->
<section class="hp-hero">
    <div class="container">

        <p class="hp-eyebrow">Events in Nepal &amp; beyond</p>

        <h1 class="hp-headline">
            The easiest way to<br>
            find &amp; book events<br>
            <span class="hp-headline-accent">you'll love.</span>
        </h1>

        <p class="hp-sub">
            Browse upcoming tech talks, concerts, workshops and more —
            all in one place. No friction, no fuss.
        </p>

        <div class="hp-actions">
            <a class="btn-hero-primary" href="<?= APP_URL ?>/events/index.php">Browse events</a>
            <?php if (!$user): ?>
                <a class="btn-hero-ghost" href="<?= APP_URL ?>/auth/register.php">Create free account</a>
            <?php else: ?>
                <a class="btn-hero-ghost" href="<?= APP_URL ?>/bookings/my-bookings.php">My bookings</a>
            <?php endif; ?>
        </div>

        <div class="hp-trust">
            <span>500+ events</span>
            <span class="dot">·</span>
            <span>12 000+ attendees</span>
            <span class="dot">·</span>
            <span>Free to join</span>
        </div>

    </div>
</section>

<!-- ── Featured Events ───────────────────────────────────────────────── -->
<section class="container hp-section">

    <div class="hp-section-head">
        <h2>Featured events</h2>
        <a class="hp-view-all" href="<?= APP_URL ?>/events/index.php">View all &rarr;</a>
    </div>

    <div class="hp-cards">

        <!-- Card 1 -->
        <a class="hp-card" href="<?= APP_URL ?>/events/show.php?id=1">
            <div class="hp-card-color" style="--c:#5C2D91;"></div>
            <div class="hp-card-body">
                <span class="hp-tag">Tech Conference</span>
                <h3>Nepal Tech Summit 2025</h3>
                <ul class="hp-card-meta">
                    <li>15 Aug 2025</li>
                    <li>Kathmandu</li>
                    <li>Rs 1,500</li>
                </ul>
                <p>500+ developers, designers &amp; founders. Nepal's biggest annual tech day.</p>
                <span class="hp-card-seats">120 seats left</span>
            </div>
        </a>

        <!-- Card 2 -->
        <a class="hp-card" href="<?= APP_URL ?>/events/show.php?id=2">
            <div class="hp-card-color" style="--c:#0369a1;"></div>
            <div class="hp-card-body">
                <span class="hp-tag">Music</span>
                <h3>Himalayan Sounds Live</h3>
                <ul class="hp-card-meta">
                    <li>22 Aug 2025</li>
                    <li>Pokhara</li>
                    <li>Rs 800</li>
                </ul>
                <p>Folk &amp; fusion music with the Annapurna range as your backdrop. 6 live acts.</p>
                <span class="hp-card-seats">45 seats left</span>
            </div>
        </a>

        <!-- Card 3 -->
        <a class="hp-card" href="<?= APP_URL ?>/events/show.php?id=3">
            <div class="hp-card-color" style="--c:#b45309;"></div>
            <div class="hp-card-body">
                <span class="hp-tag">Workshop</span>
                <h3>UI/UX Design Bootcamp</h3>
                <ul class="hp-card-meta">
                    <li>30 Aug 2025</li>
                    <li>Online · Zoom</li>
                    <li>Rs 2,000</li>
                </ul>
                <p>Figma, design systems, usability testing — two intense, hands-on days.</p>
                <span class="hp-card-seats hp-seats-low">18 seats left</span>
            </div>
        </a>

    </div>
</section>

<!-- ── How it works ──────────────────────────────────────────────────── -->
<section class="hp-how">
    <div class="container hp-section">

        <div class="hp-section-head">
            <h2>How it works</h2>
        </div>

        <div class="hp-steps">
            <div class="hp-step">
                <span class="hp-step-num">01</span>
                <h3>Discover</h3>
                <p>Filter events by category, date, or location. Find exactly what excites you.</p>
            </div>
            <div class="hp-step-divider" aria-hidden="true"></div>
            <div class="hp-step">
                <span class="hp-step-num">02</span>
                <h3>Book</h3>
                <p>Reserve your seat in under a minute. No hidden fees, no complicated flows.</p>
            </div>
            <div class="hp-step-divider" aria-hidden="true"></div>
            <div class="hp-step">
                <span class="hp-step-num">03</span>
                <h3>Attend</h3>
                <p>Show your booking confirmation at the door and enjoy every moment.</p>
            </div>
        </div>

    </div>
</section>

<!-- ── Guest CTA ─────────────────────────────────────────────────────── -->
<?php if (!$user): ?>
<section class="container hp-cta-wrap">
    <div class="hp-cta">
        <div class="hp-cta-text">
            <h2>Ready to find your next event?</h2>
            <p>Join thousands of people discovering events every week.</p>
        </div>
        <div class="hp-cta-btns">
            <a class="btn-hero-primary" href="<?= APP_URL ?>/auth/register.php">Get started — it's free</a>
            <a class="btn-hero-ghost" href="<?= APP_URL ?>/auth/login.php">Sign in</a>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* ── Tokens ──────────────────────────────────────────────────────────── */
:root {
    --ink:    #0f172a;
    --ink2:   #475569;
    --ink3:   #94a3b8;
    --line:   #e2e8f0;
    --wash:   #f8fafc;
    --accent: #5C2D91;
    --r:      10px;
}

/* ── Hero ────────────────────────────────────────────────────────────── */
.hp-hero {
    padding: 5rem 0 4rem;
    border-bottom: 1px solid var(--line);
}
.hp-eyebrow {
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--accent);
    margin: 0 0 1.4rem;
}
.hp-headline {
    font-size: clamp(2.4rem, 5vw, 3.6rem);
    font-weight: 800;
    line-height: 1.13;
    letter-spacing: -.025em;
    color: var(--ink);
    margin: 0 0 1.25rem;
}
.hp-headline-accent { color: var(--accent); }
.hp-sub {
    font-size: 1rem;
    color: var(--ink2);
    max-width: 420px;
    line-height: 1.7;
    margin: 0 0 2rem;
}
.hp-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    background: var(--accent);
    color: #fff;
    font-weight: 700;
    font-size: .92rem;
    padding: .75rem 1.4rem;
    border-radius: var(--r);
    border: 1.5px solid var(--accent);
    text-decoration: none;
    transition: opacity .15s;
}
.btn-hero-primary:hover { opacity: .86; }
.btn-hero-ghost {
    display: inline-flex;
    align-items: center;
    background: transparent;
    color: var(--ink);
    font-weight: 600;
    font-size: .92rem;
    padding: .75rem 1.4rem;
    border-radius: var(--r);
    border: 1.5px solid var(--line);
    text-decoration: none;
    transition: border-color .15s;
}
.btn-hero-ghost:hover { border-color: #b0bec5; }
.hp-trust {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .82rem;
    color: var(--ink3);
}
.hp-trust .dot { opacity: .5; }

/* ── Section scaffold ────────────────────────────────────────────────── */
.hp-section { padding: 3.5rem 0; }
.hp-section-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 1.75rem;
    border-bottom: 1px solid var(--line);
    padding-bottom: .85rem;
}
.hp-section-head h2 {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -.01em;
    color: var(--ink);
    margin: 0;
}
.hp-view-all {
    font-size: .82rem;
    font-weight: 600;
    color: var(--accent);
    text-decoration: none;
}
.hp-view-all:hover { text-decoration: underline; }

/* ── Cards ───────────────────────────────────────────────────────────── */
.hp-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.hp-card {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--line);
    border-radius: var(--r);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    background: #fff;
    transition: box-shadow .18s, transform .18s;
}
.hp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(15,23,42,.08);
}
.hp-card-color {
    height: 4px;
    background: var(--c, var(--accent));
}
.hp-card-body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .45rem;
    flex: 1;
}
.hp-tag {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--ink3);
}
.hp-card-body h3 {
    font-size: .98rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    line-height: 1.35;
}
.hp-card-meta {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    font-size: .78rem;
    color: var(--ink3);
}
.hp-card-body p {
    font-size: .86rem;
    color: var(--ink2);
    margin: 0;
    line-height: 1.55;
    flex: 1;
}
.hp-card-seats {
    font-size: .76rem;
    font-weight: 600;
    color: var(--ink3);
    margin-top: .25rem;
}
.hp-seats-low { color: #c2410c; }

/* ── How it works ────────────────────────────────────────────────────── */
.hp-how {
    background: var(--wash);
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}
.hp-steps {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto 1fr;
    align-items: start;
}
.hp-step { padding: .25rem 0; }
.hp-step-num {
    display: block;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .12em;
    color: var(--accent);
    margin-bottom: .65rem;
}
.hp-step h3 {
    font-size: .95rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 .35rem;
}
.hp-step p {
    font-size: .86rem;
    color: var(--ink2);
    margin: 0;
    line-height: 1.6;
    max-width: 210px;
}
.hp-step-divider {
    width: 1px;
    background: var(--line);
    height: 70px;
    margin: .25rem 2.5rem 0;
}

/* ── CTA ─────────────────────────────────────────────────────────────── */
.hp-cta-wrap { padding-bottom: 3.5rem; }
.hp-cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    border: 1px solid var(--line);
    border-radius: var(--r);
    padding: 2.25rem 2rem;
    flex-wrap: wrap;
}
.hp-cta-text h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 .25rem;
}
.hp-cta-text p {
    font-size: .88rem;
    color: var(--ink2);
    margin: 0;
}
.hp-cta-btns {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
    flex-shrink: 0;
}

/* ── Responsive ──────────────────────────────────────────────────────── */
@media (max-width: 860px) {
    .hp-cards  { grid-template-columns: 1fr 1fr; }
    .hp-steps  { grid-template-columns: 1fr; gap: 1.5rem; }
    .hp-step-divider { display: none; }
    .hp-cta    { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 520px) {
    .hp-hero   { padding: 2.75rem 0 2.25rem; }
    .hp-cards  { grid-template-columns: 1fr; }
    .hp-actions { flex-direction: column; }
    .btn-hero-primary,
    .btn-hero-ghost { justify-content: center; text-align: center; }
}
</style>

<?php render_footer(); ?>