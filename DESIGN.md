---
name: SokratCRM
description: "Bilingual operational CRM interface for sales, administration, and technical status workflows."
colors:
  primary-red: "#dc2637"
  primary-red-dark: "#b81829"
  page: "#f4f6fa"
  surface: "#ffffff"
  surface-soft: "#f8fafc"
  ink: "#172033"
  muted: "#596579"
  border: "#e4e8ef"
  success: "#0f7440"
  success-surface: "#e9f8ef"
  neutral-state-surface: "#eef1f5"
  dark-page: "#151922"
  dark-surface: "#202631"
  dark-surface-soft: "#272e3a"
  dark-ink: "#f3f5f8"
  dark-muted: "#aeb7c6"
  dark-border: "#333b49"
  dark-success: "#57d58c"
  dark-success-surface: "#173c2a"
typography:
  headline:
    fontFamily: "Tajawal, Tahoma, Arial, sans-serif"
    fontSize: "clamp(25px, 3vw, 32px)"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Tajawal, Tahoma, Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "normal"
  body:
    fontFamily: "Tajawal, Tahoma, Arial, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.7
    letterSpacing: "normal"
  label:
    fontFamily: "Tajawal, Tahoma, Arial, sans-serif"
    fontSize: "11px"
    fontWeight: 800
    lineHeight: 1.2
    letterSpacing: "normal"
rounded:
  xs: "6px"
  sm: "8px"
  control: "11px"
  icon: "12px"
  alert: "14px"
  panel: "16px"
  pill: "999px"
spacing:
  compact: "6px"
  xs: "8px"
  sm: "10px"
  md: "12px"
  lg: "18px"
  xl: "22px"
  shell: "32px"
components:
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "0 15px"
    height: "44px"
  search-input:
    backgroundColor: "{colors.surface-soft}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "0 42px"
    height: "44px"
  filter:
    backgroundColor: "transparent"
    textColor: "{colors.muted}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "0 13px"
    height: "44px"
  filter-active:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary-red}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "0 13px"
    height: "44px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "18px"
  status-online:
    backgroundColor: "{colors.success-surface}"
    textColor: "{colors.success}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "6px 9px"
  navigation-item:
    backgroundColor: "transparent"
    textColor: "{colors.muted}"
    rounded: "{rounded.icon}"
    padding: "8px 10px"
    height: "49px"
  navigation-item-active:
    backgroundColor: "{colors.primary-red}"
    textColor: "{colors.surface}"
    rounded: "{rounded.icon}"
    padding: "8px 10px"
    height: "49px"
---

# Design System: SokratCRM

## Overview

SokratCRM uses one shared bilingual operational interface across Arabic and English. The current system is defined by a fixed or collapsible sidebar, a light gray page canvas, white bordered surfaces, red interaction emphasis, compact controls, and green reserved for live or successful state.

New operational surfaces extend this incumbent system locally. They retain the shared sidebar and Tajawal font foundation, use logical CSS properties so RTL and LTR layouts mirror correctly, and keep task-specific data ahead of decorative or disconnected summary content.

**Key Characteristics:**

- Tajawal is the shared Arabic and Latin interface family; technical addresses remain monospace.
- White panels and soft-gray data regions sit on a pale-gray page canvas.
- Red identifies active navigation, focus, selection, and primary action emphasis.
- Green is limited to online, active, or successful state.
- Controls are compact, bordered, and generally 40–44px high.
- Light and dark modes preserve the same hierarchy and state roles.

## Colors

The palette is role-based: one red interaction accent, neutral page and data surfaces, dark blue-gray text, and a separate green state channel. Dark mode remaps the neutral and success roles while retaining red emphasis.

### Primary

- **Primary Red** (`primary-red`): active navigation, focus borders, selected filters, action emphasis, and brand text.
- **Dark Primary Red** (`primary-red-dark`): red-on-light icon and compact label text where the base accent needs stronger contrast.

### Neutral

- **Page** (`page`): default application canvas.
- **Surface** (`surface`): cards, controls, navigation, and elevated containers.
- **Soft Surface** (`surface-soft`): table headers, search fields, row hover, and grouped-control wells.
- **Ink** (`ink`): headings and primary content.
- **Muted** (`muted`): supporting copy, labels, timestamps, and inactive controls.
- **Border** (`border`): panel, control, row, and section separation.
- **Neutral State Surface** (`neutral-state-surface`): offline or unknown-state badges and device icons.
- **Dark Page, Surface, Soft Surface, Ink, Muted, and Border** (`dark-*`): direct dark-mode role replacements, not an independent palette.

### Tertiary

- **Success** (`success`): online counts, active connections, live dots, and success text.
- **Success Surface** (`success-surface`): online badges and device-icon containers.
- **Dark Success and Dark Success Surface** (`dark-success`, `dark-success-surface`): dark-mode equivalents of the same state channel.

**The State Color Rule.** Red communicates interaction or product emphasis; green communicates current positive state. Do not exchange those meanings.

## Typography

**Interface Font:** Tajawal with Tahoma, Arial, and generic sans-serif fallbacks. Local Arabic and Latin font files provide weights 400, 500, 700, 800, and 900.

**Technical Font:** Native monospace stack for IP addresses and other machine-readable values.

### Hierarchy

- **Headline** (`typography.headline`): page titles; letter spacing resets to normal in RTL.
- **Title** (`typography.title`): card identities, alerts, and compact entity names.
- **Body** (`typography.body`): explanatory copy, with page-intro lines capped near 68 characters.
- **Label** (`typography.label`): table headers, filters, metrics, state badges, and metadata.
- **Numeric status values:** 20–23px with tabular numerals where counts must align while changing.
- **Technical values:** 11px, weight 700, native monospace, LTR direction, and wrapping enabled for long addresses.

**The Script Rule.** Use Tajawal for both interface languages, remove Latin-specific negative tracking in RTL, and isolate IP addresses or machine values with LTR direction.

## Layout

The application shell is a horizontal flex layout on desktop. The shared sidebar is 288px wide and sticky at full height; its persisted collapsed state is 88px. The main operational area fills the remaining width with 32px inline padding on the Technical Support surface and no fixed maximum-width container.

Surface hierarchy is linear: page header, one full-width status or context strip, then the primary searchable data panel. Panels use 18–22px internal spacing, while compact controls use 6–15px gaps and padding. Logical properties (`inline-start`, `inline-end`, and `text-align: start`) keep the same composition in RTL and LTR.

Responsive rules are explicit:

- At 1080px and below, status metrics and table columns tighten without changing the information order.
- At 900px and below, the sidebar becomes an off-canvas drawer, the menu button appears, main padding reduces, the toolbar stacks, and data-table rows become two-column labeled cards.
- At 620px and below, main padding reduces again, header actions become full-width, status metrics tighten, and each data row becomes a single-column card.
- Overlays lock body scrolling while mobile navigation is open; focus moves into the drawer and returns to the menu control on close.

**The Reading Order Rule.** Preserve the same semantic order across breakpoints; change the table's visual layout without duplicating or reordering its facts.

## Elevation & Depth

The system combines borders with low-amplitude shadows. Borders carry most separation; shadow is reserved for whole panels, the network strip, active segmented controls, dropdowns, and mobile drawers.

### Shadow Vocabulary

- **Panel:** `0 14px 38px rgba(17,24,39,.06)` for primary light-mode surfaces.
- **Control:** `0 6px 18px rgba(17,24,39,.04)` for the refresh action.
- **Selected filter:** `0 3px 12px rgba(17,24,39,.08)` to lift the active segment within a soft group.
- **Active navigation:** `0 12px 27px #dc26372c` below the red navigation gradient.
- **Dropdown:** `0 18px 45px rgba(15,23,42,.16)` for the profile menu.
- **Dark panel:** `0 18px 42px rgba(0,0,0,.24)` on the Technical Support surface; shared dark glass surfaces may add an inset highlight.

**The Border-First Rule.** Use the established one-pixel border for ordinary separation; add shadow only when a complete surface or temporary layer must separate from the page.

## Shapes

Corners use a compact rounded scale rather than one universal radius. Panels and full-width strips use the panel radius; alerts use the alert radius; controls use the control radius; icon containers sit between control and alert radii; compact internal segments use the small radius; status badges are pills. Borders remain one pixel and icons are usually contained in square rounded tiles rather than free-floating.

Do not apply a panel radius to every child. Nested elements step down through `rounded.alert`, `rounded.icon`, `rounded.control`, `rounded.sm`, and `rounded.xs` so hierarchy remains visible.

## Components

### Buttons

- **Secondary / Refresh:** a 44px white bordered control using `button-secondary`; hover changes the border and text to red and moves the control upward by 1px.
- **Primary:** a red-filled, white-text variant is established in settings forms; it uses the same compact height and 10–11px control radius.
- **Focus:** all keyboard-focusable controls receive a 3px translucent red outline with 3px offset; fields instead use a red border and a 3px low-opacity ring.
- **Motion:** state transitions are 160–200ms; reduced-motion preference disables transitions.

### Chips

- **Filters:** three compact controls sit inside a soft-surface rounded group. The default state is transparent and muted; the active state switches to a white surface, red text, and the selected-filter shadow.
- **Status:** online and offline states use pill badges with a 7px leading dot. Online uses the success channel; offline uses the neutral-state channel.

### Cards / Containers

- **Panels:** white surface, one-pixel border, panel radius, clipped overflow, and panel shadow.
- **Status strip:** one continuous panel split by inline borders into a flexible identity region and fixed metric regions; it wraps into stacked identity and metrics rows below 900px.
- **Internal regions:** soft surfaces distinguish table headers, row hover, search wells, and technical-value capsules without introducing additional cards.

### Inputs / Fields

- **Search:** 44px high, soft-surface fill, one-pixel border, control radius, an inline-start search icon, and direction-aware padding.
- **Focus:** red border, translucent red ring, and a surface background.
- **Placeholder:** muted text with sufficient contrast in both themes.

### Navigation

- **Desktop:** 288px sticky sidebar with 49px items, 13px item corners, 32px icon tiles, 5px row gaps, and an optional 88px persisted collapsed state.
- **Active:** red gradient, white content, red border, and shallow colored shadow.
- **Hover:** pale red background and red text; desktop expanded items move 2px toward the logical inline start.
- **Mobile:** an off-canvas drawer appears below 900px with a dimmed, slightly blurred overlay. LTR opens from the left and RTL opens from the right.

### Data Table

- **Desktop:** fixed-layout columns, soft-surface header, 11px heavy labels, 16px by 18px body-cell padding, and one-pixel row dividers.
- **Responsive:** the header hides below 900px and each cell exposes its localized `data-label`; the first identity cell spans the row.
- **Interaction:** row hover uses only the soft surface; search and status filtering happen in place, with a dedicated no-match state.

## Do's and Don'ts

### Do:

- **Do** inherit the shared sidebar, profile controls, Tajawal font assets, light/dark theme behavior, and direction-aware layout.
- **Do** lead operational pages with the primary task context and place the main data surface immediately after it.
- **Do** use neutral surfaces and borders for structure, red for interaction emphasis, and green only for positive live state.
- **Do** preserve visible empty, unavailable, and no-match states inside the main data region.
- **Do** retain keyboard focus, Escape-to-close behavior, focus restoration, and reduced-motion support.

### Don't:

- **Don't** replace the continuous status strip with disconnected metric cards when the metrics describe one shared source.
- **Don't** use green as decoration, navigation emphasis, or a general secondary accent.
- **Don't** introduce a second interface font or apply the interface family to icons and technical monospace values.
- **Don't** hard-code left/right alignment where logical inline properties can preserve Arabic and English layouts.
- **Don't** add strong shadow to every nested region; ordinary hierarchy is border- and tone-led.
