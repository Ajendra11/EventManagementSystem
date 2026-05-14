<?php

declare(strict_types=1);

function render_logo(string $variant = 'default'): void
{
    ?>
    <a class="brand-logo brand-logo--<?= htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') ?>" href="<?= APP_URL ?>/index.php" aria-label="EventHub Home">
        <span class="brand-logo-mark" aria-hidden="true">
            <svg viewBox="0 0 40 40" focusable="false">
                <rect x="6" y="7" width="28" height="27" rx="9" fill="currentColor" opacity="0.14"/>
                <path d="M14 13.5h12M14 18h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M20 30s6-5.1 6-10a6 6 0 1 0-12 0c0 4.9 6 10 6 10Z" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="20" cy="20" r="2" fill="currentColor"/>
            </svg>
        </span>
        <span class="brand-logo-text">EventHub</span>
    </a>
    <?php
}
