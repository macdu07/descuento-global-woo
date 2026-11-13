---
version: 1
slug: "includes-class-dgw-admin-php"
primary_target: "includes/class-dgw-admin.php"
related_targets: ["assets/admin.css"]
---

Scope: wp-admin screens of Descuento Global Woo (campaign list + campaign editor). Mode: Operate.
Audience: non-technical shop managers; task: create, schedule, switch on/off, edit, duplicate, delete discount campaigns; read every campaign's state at a glance. Built code-led (no image generation).

## Direction contract
THESIS: The campaign list is a coupon booklet: each campaign is a coupon whose stub carries its discount. Refuses the grey WP list table with one blue accent and the dashboard-of-cards.
OWN-WORLD: Gunmetal #1D2730 shell bar and primary buttons, Cream #F8F3EA ground, white coupon rows, Turquoise #00AEAE reserved strictly for "active now", Orange #FF6B30 for scheduled, Red #EB3D40 for errors and delete. System sans, tabular numerals; the percentage numeral is the one typographic hero, repeated at list and editor scale. Perforation = dashed rule plus two punched notches between stub and row.
STORY: The manager sees which campaign is live, which is next and which ended, flips one on or off, and opens one to edit without fear of breaking prices.
FIRST VIEWPORT: Gunmetal header (title, one-line purpose, New campaign button). Below, an overlap rule line, then the coupon table: stub (percentage) | campaign name + scope | schedule with time bar and today marker | status stamp | on/off switch | Edit, Duplicate, Delete. Rows ranked active, scheduled, off, ended; columns never move. Footer signature to maurouix.com.
FORM: Coupon booklet, candidate 7 of 7 on the ordered list (hang tag, markdown stickers, promo calendar, shelf-edge label, window vinyl, open/closed sign, coupon booklet). seed:6237169ba83ef8cf
RAISES: From transit diagram: time drawn as a line (schedule bar with today marker). From orienteering map: one colour reserved for the live item. From ticket wallet: nothing disappears, it cancels (Ended stamp). From split-flap board: rows ranked by time, columns fixed. From bitmap specimen: one form (the percentage) at several sizes. From acetate manual: state changes are instant steps, no decorative easing.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
