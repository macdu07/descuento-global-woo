---
name: Descuento Global Woo
description: Campaign list and editor for WooCommerce sale campaigns, set as a coupon booklet in the author's palette.
colors:
  cream: "#f8f3ea"
  turquoise: "#00aeae"
  orange: "#ff6b30"
  red: "#eb3d40"
  gunmetal: "#1d2730"
  gunmetal-hover: "#2b3945"
  ink-soft: "#4f5b65"
  fog: "#c9d0d5"
  placeholder: "#69717a"
  paper: "#fffdf9"
  paper-ended: "#fbf8f2"
  field: "#ffffff"
  stub: "#f1e9db"
  line: "#e2d8c6"
  line-strong: "#cfc3ae"
  switch-off: "#8f8574"
  turquoise-text: "#00706f"
  turquoise-soft: "#dcf3f2"
  orange-text: "#ad4214"
  orange-soft: "#ffeadf"
  red-text: "#c0262a"
  red-soft: "#fde6e5"
  red-line: "#f5b5b4"
typography:
  display:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "48px"
    fontWeight: 800
    lineHeight: 1.1
    letterSpacing: "-0.03em"
    fontFeature: "\"tnum\" 1"
  display-list:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "34px"
    fontWeight: 800
    lineHeight: 1
    letterSpacing: "-0.03em"
    fontFeature: "\"tnum\" 1"
  headline:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "26px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.01em"
  title:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "17px"
    fontWeight: 700
    lineHeight: 1.5
  title-row:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "15px"
    fontWeight: 700
    lineHeight: 1.5
  body:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
    fontFeature: "\"tnum\" 1"
  body-sm:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif"
    fontSize: "12px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.06em"
rounded:
  xs: "6px"
  sm: "8px"
  md: "10px"
  lg: "14px"
  pill: "999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "20px"
  2xl: "28px"
  3xl: "32px"
components:
  header:
    backgroundColor: "{colors.gunmetal}"
    textColor: "{colors.cream}"
    typography: "{typography.headline}"
    rounded: "{rounded.lg}"
    padding: "28px 32px"
  button-primary:
    backgroundColor: "{colors.gunmetal}"
    textColor: "{colors.cream}"
    rounded: "{rounded.md}"
    padding: "0 18px"
    height: "42px"
  button-primary-hover:
    backgroundColor: "{colors.gunmetal-hover}"
    textColor: "{colors.cream}"
  button-light:
    backgroundColor: "{colors.cream}"
    textColor: "{colors.gunmetal}"
    rounded: "{rounded.md}"
    padding: "0 18px"
    height: "42px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.gunmetal}"
    rounded: "{rounded.md}"
    padding: "0 18px"
    height: "42px"
  button-ghost-hover:
    backgroundColor: "{colors.stub}"
  button-danger:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.red-text}"
    rounded: "{rounded.md}"
    padding: "0 18px"
    height: "42px"
  button-danger-hover:
    backgroundColor: "{colors.red-text}"
    textColor: "{colors.field}"
  icon-button:
    backgroundColor: "transparent"
    textColor: "{colors.ink-soft}"
    rounded: "{rounded.sm}"
    size: "34px"
  coupon-row:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.gunmetal}"
    rounded: "{rounded.lg}"
    padding: "18px 16px"
  coupon-row-ended:
    backgroundColor: "{colors.paper-ended}"
  coupon-stub:
    backgroundColor: "{colors.stub}"
    textColor: "{colors.gunmetal}"
    typography: "{typography.display-list}"
    width: "120px"
  coupon-stub-active:
    backgroundColor: "{colors.turquoise}"
    textColor: "{colors.gunmetal}"
  coupon-stub-scheduled:
    backgroundColor: "{colors.orange-soft}"
    textColor: "{colors.orange-text}"
  coupon-stub-off:
    backgroundColor: "{colors.stub}"
    textColor: "{colors.ink-soft}"
  stamp-active:
    backgroundColor: "{colors.turquoise-soft}"
    textColor: "{colors.turquoise-text}"
    typography: "{typography.label}"
    rounded: "{rounded.xs}"
    padding: "4px 10px"
  stamp-scheduled:
    backgroundColor: "{colors.orange-soft}"
    textColor: "{colors.orange-text}"
    typography: "{typography.label}"
    rounded: "{rounded.xs}"
    padding: "4px 10px"
  stamp-off:
    backgroundColor: "transparent"
    textColor: "{colors.ink-soft}"
    typography: "{typography.label}"
    rounded: "{rounded.xs}"
    padding: "4px 10px"
  timeline:
    backgroundColor: "{colors.stub}"
    rounded: "{rounded.pill}"
    height: "6px"
    width: "240px"
  switch-track:
    backgroundColor: "{colors.switch-off}"
    rounded: "{rounded.pill}"
    width: "42px"
    height: "24px"
  switch-track-on:
    backgroundColor: "{colors.gunmetal}"
  panel:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.gunmetal}"
    rounded: "{rounded.lg}"
    padding: "26px 28px"
  panel-stub:
    backgroundColor: "{colors.stub}"
    typography: "{typography.display}"
    width: "220px"
    padding: "26px 24px"
  input-text:
    backgroundColor: "{colors.field}"
    textColor: "{colors.gunmetal}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: "8px 12px"
    height: "42px"
  scope-option:
    backgroundColor: "{colors.field}"
    textColor: "{colors.ink-soft}"
    rounded: "{rounded.md}"
    padding: "14px 16px"
  scope-option-selected:
    backgroundColor: "{colors.stub}"
    textColor: "{colors.gunmetal}"
  feedback:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.gunmetal}"
    rounded: "12px"
    padding: "14px 16px"
  feedback-error:
    backgroundColor: "{colors.red-soft}"
    textColor: "{colors.gunmetal}"
---

# Design System: Descuento Global Woo

## Overview

**Creative North Star: "The Coupon Booklet"**

Every campaign is a coupon torn from the same booklet. The list is a stack of coupons on a cream counter, each with a stub that carries its percentage and a perforated edge with two punched notches; the editor is that same coupon opened up at full size. The percentage numeral is the single typographic hero and appears as one form at several sizes (48px in the editor, 34px in the list, 28px on narrow screens). Everything else is quiet system sans in gunmetal on cream and warm paper.

The screens live inside wp-admin beside native WooCommerce pages, so the system works with the platform instead of hiding it: the admin's own system sans, its Dashicons, and its native radios and checkboxes, retinted to gunmetal through the admin theme color. Color is a state language, not decoration. Turquoise means "running right now" and nothing else; orange means "scheduled"; red means "error or destroy". A manager who comes back twice a year can tell which campaign is live, which is next and which has ended without reading a word.

State changes are instant steps. The only motion is a 150ms ease-out on color and the switch thumb, and it switches off entirely under reduced motion. Ended campaigns stay in the booklet, cancelled rather than removed: the numeral is struck through and the stamp goes dashed.

**Key Characteristics:**
- Coupon rows with a tinted stub, a 2px dashed perforation and two half-circle punched notches.
- One typographic hero: the 800-weight percentage numeral, repeated at every scale.
- Turquoise reserved strictly for "active now"; orange for scheduled; red for errors and delete.
- Gunmetal carries structure and command: header shell, primary buttons, switch on, focus rings, native controls.
- Flat paper on cream; borders and tint, not shadows, separate surfaces.
- Rows ranked active, scheduled, off, ended; columns never move.

## Colors

Five owner-pinned brand colors (CM-Cream, CM-Turquoise, CM-Orange, CM-Red, CM-Gunmetal), each with derived text and soft tints so every state stays legible on paper.

### Primary
- **CM-Gunmetal** (gunmetal): the structural and command color. Header shell, primary buttons, the switch when on, every focus ring, body ink, links, the editor's numeral underline, and WordPress native controls via the admin theme color (with darker steps #141b21 and #0b0f12). Its hover step is **Lifted Gunmetal** (gunmetal-hover).

### Secondary
- **CM-Turquoise** (turquoise): "active now" only. Fills the active coupon's stub and the active timeline. Its text step **Deep Lagoon** (turquoise-text) sets the active relative time and stamp label; **Lagoon Wash** (turquoise-soft) fills the active stamp.

### Tertiary
- **CM-Orange** (orange): "scheduled". The pure hue is too light for text, so the scheduled numeral, relative time and stamp use **Burnt Orange** (orange-text) on **Apricot Wash** (orange-soft).
- **CM-Red** (red): errors and destruction. The pure red marks invalid field borders and the danger button outline; **Brick** (red-text) is for error copy, danger labels and the danger hover fill; **Blush** (red-soft) fills error banners and the delete icon hover; **Blush Line** (red-line) borders the error banner and the danger zone.

### Neutral
- **CM-Cream** (cream): the page ground behind everything, and the fill of the punched notches so they read as holes through the coupon. It is also the text color on gunmetal.
- **Warm Paper** (paper): coupon rows, panels, banners. **Faded Paper** (paper-ended) is used only behind ended rows.
- **Stub Kraft** (stub): coupon stubs, the timeline track, ghost and icon hover fills, selected scope option, category chips.
- **Perforation Tan** (line) and **Deep Tan** (line-strong): hairline borders of rows and panels, and the stronger rule used for the dashed perforation, input strokes and the inactive timeline fill.
- **Slate Ink** (ink-soft): secondary copy, hints, column headers, off and ended numerals and stamps. Chosen for contrast on paper and stub.
- **Fog** (fog): secondary text on the gunmetal header, including off and ended stamps shown there.
- **Placeholder Grey** (placeholder): input placeholders, darkened from the default for contrast.
- **Switch Off Taupe** (switch-off): the off switch track, dark enough to read as a control against paper (hovers to #7b7262).
- **Field White** (field): input, select and scope option backgrounds, so editable areas sit brighter than the paper.

### Named Rules
**The Live Color Rule.** Turquoise means "this campaign is applying discounts right now". It appears only on the active stub, the active timeline fill, the active relative time and the active stamp. Never use it for buttons, links, focus, success messages or decoration.

**The Gunmetal Command Rule.** Anything the manager acts with (primary buttons, the switch when on, focus outlines, radios and checkboxes) is gunmetal, never a state color. State colors describe; gunmetal commands.

**The Text Step Rule.** Pure orange and pure red never set text on light grounds. Use their text steps; the pure hues are for fills, outlines and invalid borders.

## Typography

**Display Font:** the WordPress admin system sans (-apple-system, Segoe UI, Roboto and the rest of the admin stack)
**Body Font:** the same system sans
**Label/Mono Font:** none distinct; tabular numerals are on across the whole screen

**Character:** One family, admin-native, so the plugin sits comfortably beside WooCommerce screens. Hierarchy comes from weight and size, and the 800-weight percentage numeral carries all the drama.

### Hierarchy
- **Display** (800, 48px, 1.1, -0.03em): the editable percentage in the editor stub, set on a 2px gunmetal underline with a 24px/700 percent sign. The blank empty-state coupon uses the same size with an em dash.
- **Display-list** (800, 34px, 1, -0.03em): the percentage on each coupon stub, with a 16px/700 raised percent sign. Drops to 28px (sign 14px) at 960px and below.
- **Headline** (700, 26px, 1.2, -0.01em): the page title in the gunmetal header; the one-line purpose under it is body text in fog, capped at 62ch.
- **Title** (700, 17px): panel headings in the editor. The empty-state heading steps up to 20px; the danger-zone heading down to 15px.
- **Title-row** (700, 15px): the campaign name link on each coupon row; underlines on hover.
- **Body** (400, 14px, 1.5): everything else, with tabular numerals. Scope lines cap at 42ch, intros at 56ch.
- **Body-sm** (13px): hints, field help and the relative-time line under each timeline (600 weight when the campaign is active or scheduled).
- **Label** (700, 12px, 0.06em, uppercase): status stamps only. Column headers use 12px/600 sentence case in slate ink.

### Named Rules
**The One Hero Rule.** The percentage numeral is the only display-weight type on the screen. Headings stay at 700 and at most 26px so the discount always wins.

**The Tabular Rule.** Numerals are tabular everywhere, so percentages and dates line up down the booklet.

## Layout

The work area caps at 1180px and sits in wp-admin's content column (20px top margin, 40px bottom). The page is a vertical stack: gunmetal header, any feedback banner, a one-line rule note, then the coupon table or the editor panels, then the footer signature (12px, centered, slate ink).

The list is a real table with 10px vertical spacing between rows, so each row reads as a separate coupon. Columns are fixed and never reorder: stub (120px) | name and scope | schedule with dates, timeline and relative time (min 230px) | stamp | switch (64px) | actions (130px). Rows are ranked active, scheduled, off, ended.

The editor is a column of panels with 16px between them. The first panel is the coupon itself: a 220px stub with the percentage input beside a body holding name and the on/off toggle. Later panels hold two-column field grids (18px gap) and a two-up scope choice.

Rhythm is built on 4px steps: 4/8/12/16/20/28/32. Panels pad 26px by 28px, the header 28px by 32px, row cells 18px by 16px.

**At 960px and below**, the table drops its header and each coupon becomes a three-column grid card (112px stub | content | switch). The stub spans the card's height, the switch shows its visible label ("On", "Off", "On · ended"), and actions move under the content. The editor's coupon panel stacks: the perforation turns horizontal under the stub and the notches are dropped.
**At 782px and below**, the header stacks with 22px padding, field grids and scope options go to one column, panels pad 20px, and the danger zone stacks.

## Elevation & Depth

The system is flat. Depth comes from tone and line: cream ground, warm paper surfaces with 1px tan borders, kraft stubs, and a gunmetal header slab. The only shadow in use is the small lift under the switch thumb (`0 1px 2px rgba(29, 39, 48, 0.3)`). Focus and selection use outlines and inset strokes, not glows: 2px gunmetal outlines offset 2px, a 1px gunmetal ring on focused inputs, and an inset 1px gunmetal stroke on the selected scope option.

### Named Rules
**The Paper-on-Counter Rule.** Surfaces separate by border and tint, never by drop shadow. If a new surface needs to stand out, give it a tan border or a kraft fill.

## Shapes

Coupons and panels have softly rounded corners (14px), buttons and scope options slightly tighter (10px), inputs and icon buttons 8px, stamps, chips and the dismiss button 6px. Switches and timelines are full pills.

The signature geometry is the perforation: a 2px dashed tan rule between stub and body, with two 16px half-circles in cream clipped onto its top and bottom edges so they read as holes punched through the coupon. On a row the perforation is vertical; in the stacked editor it turns horizontal and the notches are dropped. Stamps are rectangular with a 1.5px border in their own text color, dashed for ended, never rotated.

## Components

### Buttons
Solid, compact and plainly clickable.
- **Shape:** gently rounded (10px), 42px tall, 18px side padding, 14px/600 label, optional 18px Dashicon with an 8px gap.
- **Primary:** gunmetal fill, cream label; hovers to lifted gunmetal.
- **Light:** cream fill with gunmetal label, used only on the gunmetal header (New campaign); hovers to white. Focus outlines on the header turn cream.
- **Ghost:** transparent with a deep tan border (Cancel); hovers to stub kraft.
- **Danger:** paper fill, red outline, brick label; hovers to a brick fill with white label. Destructive forms confirm before submitting.
- **Icon buttons:** 34px square, 8px radius, slate ink glyph; hover to stub kraft with gunmetal glyph, or to blush with brick for delete. Each carries a title and screen-reader text.
- **Motion:** background, color and border change over 150ms ease-out.

### Coupon Row (signature)
- **Structure:** warm paper row, 1px perforation-tan border, 14px outer corners, kraft stub on the left with the percentage numeral, dashed perforation and punched notches.
- **Active:** turquoise stub (perforation darkens to 35% gunmetal), turquoise timeline with a 12px gunmetal "today" marker ringed in paper, deep lagoon relative time at 600, active stamp.
- **Scheduled:** apricot stub, burnt orange numeral and relative time, scheduled stamp.
- **Off:** kraft stub, slate ink numeral.
- **Ended:** slate ink numeral struck through (2px), faded paper behind the row, dashed stamp. Ended rows stay in the list.

### Status Stamp
- **Style:** 12px/700 uppercase, 0.06em tracking, 4px by 10px padding, 1.5px border in the stamp's own color, 6px radius.
- **Variants:** active (deep lagoon on lagoon wash), scheduled (burnt orange on apricot wash), off (slate ink, no fill), ended (slate ink, dashed border). On the gunmetal header, off and ended turn fog.

### Schedule Timeline
- **Style:** 6px pill track in stub kraft, max 240px wide; the fill shows elapsed time, deep tan by default and turquoise only when active. Carries an accessible label describing progress. Dates above use an arrow separator; relative time below.

### Switch
- **Style:** 42px by 24px pill, white 18px thumb; switch-off taupe when off, gunmetal when on, thumb slides 18px over 150ms ease-out.
- **Hover:** off darkens to #7b7262; on goes to lifted gunmetal.
- **Labels:** in the list it is an ARIA switch; at 960px and below it shows a visible 600-weight label ("On", "Off", "On · ended"). In the editor the same track is driven by a hidden checkbox with a bold title and explanation beside it.

### Cards / Containers (panels)
- **Corner Style:** 14px.
- **Background:** warm paper, 1px perforation-tan border, no shadow.
- **Internal Padding:** 26px by 28px (20px on small screens). Headings at title size with a slate ink intro below.
- **Coupon panel:** the editor's first panel and the empty state reuse the coupon form: 220px kraft stub, perforation and notches, body beside it. The empty state is one blank coupon with an em-dash numeral and a single primary call to action.
- **Danger zone:** no fill (sits on cream), blush-line border, 14px radius, 20px by 24px padding, heading and explanation beside the danger button.

### Inputs / Fields
- **Style:** white field, 1px deep tan stroke, 8px radius, 42px tall, 8px by 12px padding, placeholder grey placeholders. Labels 600 weight; optional notes 12px/500 slate ink beside the label; help text 13px slate ink below.
- **Focus:** border turns gunmetal with a 1px gunmetal ring; no outline glow. Select2 category pickers match, with kraft chips at 6px radius.
- **Percentage input:** borderless 48px/800 numeral on a 2px gunmetal underline that thickens on focus.
- **Error:** the failing field gets `aria-invalid`, a pure red border with a 1px red ring (the percentage underline turns red), a brick inline message beneath, and receives focus and scrolls into view.

### Scope Option
- **Style:** white tile, 1px deep tan border, 10px radius, 14px by 16px padding, native radio in gunmetal, bold title over slate ink explanation.
- **Hover:** border darkens to slate ink. **Selected:** kraft fill with gunmetal border plus inset gunmetal stroke.

### Header
- **Style:** gunmetal slab, 14px radius, 28px by 32px padding, cream 26px/700 title, fog purpose line. Holds the light button on the list, and a back link plus the campaign's stamp on the editor.

### Feedback Banner
- **Placement:** directly under the header, before any content.
- **Style:** paper, deep tan border, 12px radius, 14px by 16px padding, 18px Dashicon in gunmetal, slate ink copy, dismiss button at the end. Errors switch to blush fill, blush-line border and a brick icon, and use `role="alert"`; success uses `role="status"`.

## Do's and Don'ts

### Do:
- **Do** reserve turquoise for the active stub, active timeline, active relative time and active stamp, and nothing else.
- **Do** carry every new command (buttons, toggles, focus rings, native controls) in gunmetal, with cream on the header.
- **Do** repeat the percentage numeral at 800 weight as the one hero (48px editor, 34px list, 28px at 960px and below).
- **Do** set orange and red text with their text steps (#ad4214, #c0262a) and keep the pure hues for fills, outlines and invalid borders.
- **Do** keep ended campaigns visible: strike the numeral through and dash the stamp border.
- **Do** keep motion to 150ms ease-out on color and the switch thumb, and remove it under prefers-reduced-motion.
- **Do** render errors under the header and mark the failing field with aria-invalid, a red border, an inline message and focus.
- **Do** give narrow-screen switches a visible text label.

### Don't:
- **Don't** use turquoise for success messages, links, buttons or decoration; that breaks the one live-item color.
- **Don't** separate surfaces with drop shadows; use tan borders and kraft tint.
- **Don't** rotate stamps or style them as playful stickers; they are square-set status labels.
- **Don't** reorder table columns between states or screen sizes; rank rows instead.
- **Don't** make placeholders, the off switch track or secondary text lighter than #69717a, #8f8574 and #4f5b65.
- **Don't** add decorative easing, bounces or entrance animation.
