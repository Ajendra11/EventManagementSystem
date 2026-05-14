# EventHub Modernization — How to Apply

## What changed

| File | What was updated |
|---|---|
| `Theme.css` | Google Fonts (Plus Jakarta Sans), warmer background (`#faf9f7`), warm-tinted shadows, richer page glow |
| `modern.css` | **NEW** — visual enhancement layer: spring buttons, card hover lifts, image zoom, pill badges, better focus rings, login warmth, browse page improvements |

## One-time setup (takes 2 minutes)

### Step 1 — Add the font preconnect to your PHP layout

In your main layout PHP file (probably `includes/header.php` or `layout.php`), add these
two lines in the `<head>` **before** your CSS links:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

### Step 2 — Link modern.css after app.css

In the same layout file, find where `app.css` is linked and add `modern.css` right after:

```html
<link rel="stylesheet" href="assets/css/app.css">
<link rel="stylesheet" href="assets/css/modern.css">   <!-- ADD THIS -->
```

That's it. The modernization works on top of your existing CSS — no class names were changed,
no PHP files need editing, and all existing styles are preserved.

---

## What you'll see

- **Font** — Plus Jakarta Sans replaces Inter. Friendlier, rounder, great readability.
- **Brand name** — Gradient text (purple → cyan). Pops beautifully.
- **Buttons** — Pill shaped, spring bounce on hover, colored drop shadows.
- **Cards** — Hover lifts with a soft upward float.
- **Event cards** — Image zoom on hover + lift effect on both light and dark cards.
- **Badges** — Pill shaped, uppercase, clean.
- **Form inputs** — Purple focus ring, smooth hover border.
- **Login page** — Warmer gradient background, better card glow.
- **Browse filter bar** — Slightly softer dark panel with purple accent border.
- **Scrollbar** — Purple-tinted thumb (subtle).
- **Tables** — Soft purple row highlight on hover.
- **Dark mode** — All enhancements adapt properly.
- **Reduced motion** — Automatically disables all animations if user prefers it.

---

## Reverting

Remove the `<link>` tag for `modern.css`. Nothing else was permanently changed in behaviour —
`Theme.css` token updates are the only structural change, and those only affect visual values.
