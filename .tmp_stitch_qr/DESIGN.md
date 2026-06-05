---
name: The Seamless Pour
colors:
  surface: '#fcf9f8'
  surface-dim: '#dcd9d9'
  surface-bright: '#fcf9f8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3f2'
  surface-container: '#f0eded'
  surface-container-high: '#eae7e7'
  surface-container-highest: '#e5e2e1'
  on-surface: '#1c1b1b'
  on-surface-variant: '#5d3f3c'
  inverse-surface: '#313030'
  inverse-on-surface: '#f3f0ef'
  outline: '#916f6b'
  outline-variant: '#e6bdb8'
  surface-tint: '#c00012'
  primary: '#bb0011'
  on-primary: '#ffffff'
  primary-container: '#e12626'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb4ab'
  secondary: '#80534a'
  on-secondary: '#ffffff'
  secondary-container: '#ffc4b9'
  on-secondary-container: '#7b4e46'
  tertiary: '#52613b'
  on-tertiary: '#ffffff'
  tertiary-container: '#6a7a51'
  on-tertiary-container: '#faffe9'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdad6'
  primary-fixed-dim: '#ffb4ab'
  on-primary-fixed: '#410002'
  on-primary-fixed-variant: '#93000b'
  secondary-fixed: '#ffdad4'
  secondary-fixed-dim: '#f3b9ae'
  on-secondary-fixed: '#32120c'
  on-secondary-fixed-variant: '#653c34'
  tertiary-fixed: '#d7e9b8'
  tertiary-fixed-dim: '#bbcd9e'
  on-tertiary-fixed: '#131f02'
  on-tertiary-fixed-variant: '#3d4c27'
  background: '#fcf9f8'
  on-background: '#1c1b1b'
  surface-variant: '#e5e2e1'
  crema-white: '#FCFAFA'
  froth-gray: '#F2F2F2'
  espresso-black: '#1A1A1A'
  roast-red: '#E82C2A'
  bean-brown: '#522C25'
  success-green: '#CADCAC'
typography:
  display-lg:
    fontFamily: ebGaramond
    fontSize: 48px
    fontWeight: '300'
    lineHeight: '1.1'
  headline-md:
    fontFamily: chivo
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-md:
    fontFamily: workSans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-mono:
    fontFamily: spaceMono
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.4'
    letterSpacing: 0.05em
  price-display:
    fontFamily: spaceMono
    fontSize: 18px
    fontWeight: '700'
    lineHeight: '1'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  section-gap: 50px
  container-padding: 24px
  gutter: 16px
  stack-sm: 8px
  stack-md: 16px
---

# QR Coffee Order — System Design & Style Reference
> "The Seamless Pour" - A modern, QR-integrated table ordering experience.

**Theme:** hybrid (Warm professional)

## 1. Concept & User Flow
The system is designed for a frictionless "Scan-Order-Sip" journey. Each table has a unique QR code (e.g., `coffee.app/table/12`) that pre-fills the table identifier in the ordering system.

**Key Features:**
- **Table Identification:** Automatic table number detection via URL params.
- **Dynamic Menu:** Categorized items with real-time availability.
- **Order Customization:** Milk choices, sugar levels, and special notes.
- **Cart & Checkout:** Digital payment integration or "Pay at Counter" options.
- **Live Status:** Real-time feedback on order preparation.

---

## 2. Visual Identity (Design Tokens)

### Colors — "The Brew Palette"
| Name | Value | Role |
|------|-------|-------|
| Espresso Black | `#1A1A1A` | Primary text, heavy headings |
| Crema White | `#FCFAFA` | Page background |
| Roast Red | `#E82C2A` | Primary CTA, active states, brand accents |
| Bean Brown | `#522C25` | Secondary text, subtle borders |
| Froth Gray | `#F2F2F2` | Card backgrounds, disabled states |
| Success Green | `#CADCAC` | Order status "Ready" |

### Typography
- **Display Headings:** `Editorial Old` (Weight 300) — For "Welcome to Table 12" and category names.
- **Subheadings:** `Little Amps` — For item names.
- **Body & UI:** `Surt` or `GT America` — For descriptions, prices, and buttons.
- **Technical/Price:** `Necto Mono` — For prices and small labels.

### Shapes & Spacing
- **Border Radius:** 25px for product cards, 50px for pill-shaped buttons.
- **Spacing:** Generous 50px section gaps.
- **Shadows:** Minimal, use `#F2F2F2` background shifts.

---

## 3. UI Components

### Table Header
Sticky bar: Logo (Left), Table Badge (Roast Red pill, Right).

### Category Navigation
Horizontal scrolling tabs (Espresso, Filter, Pastry, Brunch) using Necto Mono.

### Product Card
Background: `#F2F2F2`, Radius: 25px. Image top, Name (Little Amps), Price (Necto Mono), Add button (Pill Roast Red).

### Order Bar (Floating)
Persistent bottom bar: "My Order (3 items)", Total Price, "View Cart" Button (Full-width Roast Red pill).