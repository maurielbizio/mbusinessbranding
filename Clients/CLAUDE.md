# Clients Workspace — Mbusiness Branding AI

This folder contains individual client project workspaces managed under **Mbusiness Branding AI**.

Each subfolder represents a single client and contains its own `CLAUDE.md` with that client's specific plan, roadmap, and credentials.

---

## Folder Structure

```
Clients/
├── CLAUDE.md                  ← This file (global client standards)
├── ClientName/
│   ├── CLAUDE.md              ← Client-specific plan & roadmap
│   ├── assets/                ← Logos, brand files, images
│   ├── content/               ← Scripts, captions, copy
│   ├── docs/                  ← Contracts, onboarding docs, notes
│   └── credentials.md         ← Logins & access (never commit)
```

---

## Client Onboarding Workflow

1. **Discovery call** — gather business info, goals, pain points
2. **Create client folder** inside `/Clients/` using business name (no spaces)
3. **Create CLAUDE.md** — fill in business profile, services, roadmap
4. **Asset collection** — logo, brand colors, photos, existing accounts
5. **Platform audit** — document what exists, what needs to be built
6. **Kickoff build** — execute roadmap phase by phase
7. **Handoff & training** — walk client through what was built

---

## Standard Services (Available for All Clients)

| Service | Description |
|---------|-------------|
| **Website** | WordPress via ZipWP — lead capture, booking, SEO |
| **Social Media Setup** | Create/optimize all platforms — branded, consistent |
| **AI Receptionist** | 24/7 call handling + appointment booking via GHL |
| **AI Voice Agent** | Custom voice flows for calls (leads, recruiting, support) |
| **Google Review System** | Automated review request workflows via GHL |
| **Google Business Profile** | Setup, optimize, and maintain GMB listing |
| **Driver/Staff Recruiting Page** | Job application page + AI voice agent for applicants |
| **Social Media Content** | Scripts, captions, scheduling via GHL |

---

## Tech Stack Used Across All Clients

| Tool | Use Case |
|------|----------|
| **Go High Level (GHL)** | CRM, automation, AI receptionist, review system |
| **WordPress + ZipWP** | Website builds |
| **N8n** | Custom automation workflows |
| **RankMath** | SEO on WordPress sites |
| **Astra Theme** | WordPress theme standard |
| **Google Business Profile** | Local SEO & reputation |

---

## Standards & Rules

- **Never store real passwords in any committed file** — use `credentials.md` and add to `.gitignore`
- **Always phase the roadmap** — don't build everything at once; deliver in logical milestones
- **Document as you build** — update the client's `CLAUDE.md` with status after each phase
- **Confirm before any spend** — hosting, ads, subscriptions must be approved by Mauriel first
- **Brand consistency** — all platforms use the same handle, logo, colors, and voice

---

## Brand Voice Standard (Applied to All Clients)

Unless the client specifies otherwise:
- Professional, friendly, local, trustworthy
- No industry jargon without explanation
- CTAs are always clear and direct

---

## Status Tracking

Use the following labels in each client's CLAUDE.md to track phase progress:

| Label | Meaning |
|-------|---------|
| `[ ]` | Not started |
| `[~]` | In progress |
| `[x]` | Complete |
| `[!]` | Blocked / needs attention |
