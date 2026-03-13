# Go High Level (GHL) — Mbusiness Branding AI

## What This Folder Is
Documentation, workflows, snapshots, and automation configs for the GHL account
powering Mbusiness Branding AI's client delivery system.

---

## GHL's Role in the Stack

| Function | GHL Feature Used |
|----------|-----------------|
| Client management | CRM — contacts, pipelines, opportunities |
| AI receptionist / chat | Conversations, AI bot, SMS/email automations |
| Appointment booking | Calendars, booking widgets |
| Lead capture | Forms, funnels, landing pages |
| Reputation management | Reviews widget, Google review request automations |
| Client onboarding | Workflows, automated email/SMS sequences |
| Reporting | Dashboards, opportunity pipeline tracking |

---

## Account Structure

| Item | Details |
|------|---------|
| **Account type** | Agency (sub-accounts per client) |
| **Primary use** | AI receptionist, booking, reputation, automations |
| **Sub-account template** | Founders VIP snapshot (to be built) |

---

## Founders VIP Program — GHL Deliverables

The core product being sold. Each client gets a sub-account with:

1. **AI-Powered Lead Capture Website** — funnel or site with lead form connected to CRM
2. **24/7 AI Receptionist** — conversation bot handling inquiries + booking via calendar
3. **Reputation Management** — automated review request sequence post-job
4. **Social Media Posting** — (handled outside GHL — see Instagram AI / YouTube folders)

---

## Key Automations to Build

| Automation | Trigger | Actions |
|------------|---------|---------|
| New lead intake | Form submission | Add to CRM, send welcome SMS, notify owner |
| AI receptionist handoff | Bot conversation → human needed | Alert owner via SMS/email |
| Appointment reminder | 24hr + 1hr before booking | SMS + email to client |
| Review request | Job completed tag added | 2-step SMS sequence asking for Google review |
| No-show follow-up | Appointment missed | Automated reschedule SMS |
| Lead nurture | No response after 48hr | Follow-up sequence (3 touches) |

---

## Snapshot / Template Strategy

Build one master sub-account snapshot for Founders VIP clients:
- Pre-built pipeline: New Lead → Contacted → Booked → Job Done → Review Requested
- Pre-built automations (see above)
- Pre-built AI bot with home services FAQ responses
- Placeholder calendar connected to booking widget
- Review request workflow ready to activate

Deploy snapshot to each new client sub-account → customize name/phone/details → go live.

---

## Rules for Claude

- Never publish or activate automations on live client accounts without explicit confirmation
- Never send test messages to real contacts
- Flag any workflow that touches billing or payment before building
- When building automations, always note which GHL plan tier the feature requires (Starter vs Pro vs higher)

---

## Lead Magnet Funnel — Assets Built (March 2026)

### Tags (17 created)
| Tag | ID | Purpose |
|-----|----|---------|
| `lm-optin` | wuCQ6ZPneML6BktuR3MS | Opted into lead magnet |
| `lm-intake-sent` | 74RwQF2db6ilSFwlG502 | Intake form link sent |
| `lm-intake-completed` | vLBfjJoUBwzORdepqhdx | Intake form submitted |
| `lm-content-building` | 1RJu9r8bqyStwZSGr3YW | Content being built by owner |
| `lm-content-ready` | EnyfrYhKDijb49zDdr8w | Drive link added — triggers delivery |
| `lm-start-delivery` | yuJws6sbXarUN04mC1QG | Fires Workflow 3 |
| `lm-week1-sent` | PrbyyQImDR7qmEleiT73 | Week 1 delivered |
| `lm-week2-sent` | tE2XWysMjbPTwJVIARbX | Week 2 delivered |
| `lm-sequence-complete` | wjNQxpCpwcXIq9hfc6WF | Full sequence done |
| `lm-vip-offer-sent` | 7IK7yG1iZ3i3pRA9D9wF | Founders VIP pitched |
| `lm-booked-call` | Nodogbi9IRNhjngtaEFE | Booked Strategy Call from funnel |
| `lm-no-intake` | AA8m3nn47Z8l1XTF9G3F | Opted in, never submitted intake |
| `lm-unresponsive` | W9yXiHsIdTbsdpyuaNOK | No response after full sequence |
| `review-requested` | DO13drCvXgtbejuvNmR0 | Google review request sent |
| `review-completed` | W8me8o5du3JWVwnVywOb | Left a Google review |
| `founders-vip-prospect` | 5jqsCfnPwUS5quhIa9TI | Actively being pitched VIP |
| `founders-vip-client` | DwHGJakXDuK1vl4y6e9q | Converted to paying client |

### Custom Field
| Field | Key | ID |
|-------|-----|----|
| Content Drive Link | `contact.content_drive_link` | nJlCY9J5Zhe7ykzjvqjE |

### Custom Value
| Name | Key | ID | Action Required |
|------|-----|----|-----------------|
| Google Review Link | `{{ custom_values.google_review_link }}` | 8XMELB8FS7W5JQeuyT0O | **Replace placeholder value in GHL Settings > Custom Values** |

### Email Templates (10)
| Template | ID | Subject |
|----------|----|---------|
| LM-Email-01 | 69b039056751e628520b571f | Your 14 days of social media content — one quick step |
| LM-Email-02 | 69b03909bc278ad5d08369f4 | Are you still in? |
| LM-Email-03 | 69b0390cecfdb0c5e3827cbc | Intake received — I am on it |
| LM-Email-04 | 69b03915e412836080f78b49 | Your Week 1 content is here — ready to post |
| LM-Email-05 | 69b0391d0c60ed56ccfa4e67 | Why your competitor is getting calls you should be getting |
| LM-Email-06 | 69b0392cc99bdc0fd19ebde7 | Week 2 is here + a limited offer for you |
| LM-Email-07 | 69b0393a811dd53e3a3c4987 | The busiest owners I know said the exact same thing |
| LM-Email-08 | 69b039420c60ed4d48fa50e1 | Founders VIP update |
| LM-Email-09 | 69b03945e71e00455a8c94d0 | This is the last time I will bring this up |
| LM-Email-10 | 69b03948bc278a5d38836e4f | Quick favor — takes 30 seconds |

### Workflows to Build (manual — see build guide)
| Workflow | Trigger | Status |
|----------|---------|--------|
| LM-01 \| Opt-In Received | Opt-in form submitted | ⬜ Build manually |
| LM-02 \| Intake Form Received | Intake form submitted | ⬜ Build manually |
| LM-03 \| 14-Day Delivery + Nurture | Tag: `lm-start-delivery` | ⬜ Build manually |
| LM-04 \| Google Review Request | Tag: `lm-week2-sent` | ⬜ Build manually |

### Pipeline to Build (manual)
Lead Magnet Funnel — stages: Opted In → Intake Received → Content In Progress → Content Delivered → VIP Pitch Active → Call Booked → Converted → Ghosted

### Before Go-Live — Replace These Placeholders
| Placeholder | Where | Replace With |
|-------------|-------|-------------|
| `[INTAKE_FORM_LINK]` | Email-01, Email-02, SMS 1, SMS 2 | Intake form URL |
| `[STRATEGY_CALL_LINK]` | Email-06, 07, 08, 09, SMS 5, 6, 7 | Strategy Call booking page URL |
| `PASTE_YOUR_GOOGLE_REVIEW_LINK_HERE` | GHL Settings > Custom Values | Your Google Business Profile review URL |

---

## Key Files

| File | Contents |
|------|---------|
| [Lead_Magnet_Build_Guide.md](Lead_Magnet_Build_Guide.md) | Step-by-step instructions to build the pipeline, 2 forms, and 4 workflows manually in GHL UI |
