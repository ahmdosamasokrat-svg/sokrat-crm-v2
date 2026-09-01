# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Authenticated, active SokratCRM employees use the application to manage customer and operational work. Every authenticated, active user may access the Technical Support network-status page.

## Product Purpose

SokratCRM centralizes sales and operational workflows. The Technical Support area gives employees a current view of the devices visible to the CRM server in its Tailscale network, including their Tailscale IP addresses and online state.

## Operating Context

The application runs as a Laravel web application on a server connected to Tailscale. Device status is read from that server's local Tailscale client; it is not maintained as separate CRM data.

## Capabilities and Constraints

- The Technical Support page is available only after CRM authentication and active-account checks.
- All authenticated, active CRM users can see the page.
- The device inventory is limited to nodes visible in this server's Tailscale network map.
- Both IPv4 and IPv6 Tailscale addresses are shown when available.
- Tailscale unavailability must produce a recoverable page state rather than an application error.

## Brand Commitments

The product name is SokratCRM. New operational pages inherit the existing bilingual Arabic/English interface, shared sidebar, typography, and visual system.

## Evidence on Hand

The existing Laravel routes, shared CRM sidebar, localized copy, and installed Tailscale client are the implementation authority. No external availability guarantee or monitoring history is claimed.

## Product Principles

- Keep operational state quickly scannable.
- Show network facts from the source rather than duplicating them.
- Preserve useful failure states when infrastructure is unavailable.
- Keep new workflows consistent with the existing bilingual CRM.
