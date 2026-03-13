# Best Instantly AI Settings in 2026
**Creator:** Felipe Fuhr
**Channel:** Felipe Fuhr (runs sevenfigure-operations.com)
**Video:** https://www.youtube.com/watch?v=jTKNZt_i5c0
**Date Researched:** 2026-03-13
**Duration:** ~40 minutes
**Core Topic:** Instantly.ai cold email settings — 4-phase framework
**Credibility:** 7 years cold email, 20M+ emails sent, 20,000+ opportunities generated

---

## Framework: 4 Phases

1. **Building the Base** — Domains, email accounts, warm-up settings
2. **Deploying the Army** — Scripts, lead list, campaign logic
3. **Capturing Intel** — Analytics, inbox testing
4. **Refining & Scaling** — Automations that run everything

---

## Phase 1: Building the Base

### Email Account Types (Tradeoffs)

| Provider | Cost | Volume | Notes |
|----------|------|--------|-------|
| Microsoft 365 | $7/mo | High (3–4x Azure) | Best deliverability + volume |
| Google Workspace | $7/mo | High | Same as M365 |
| Azure (Microsoft comm servers) | $0.32–$1/mo | Low | Cheap but strict sending limits |
| SMTP (custom) | ~$0.10/inbox | Low | Cheapest but riskiest |

**Key insight:** Diversify across ALL providers. Never rely on just one.
- If one goes down, the pipeline doesn't dry up
- All providers experience downtime — it's "when not if"
- 14-day re-warm-up period if an account gets banned → painful if your only provider
- Can only compare results when systems run in parallel

### Email Account Settings

**Signatures:**
- Do NOT add signatures in account settings
- Add them via spin syntax in the campaign copy instead
- Reason: Eliminate common denominators so ESPs can't fingerprint your bulk sends

**Tags (3 categories):**
- `ready-to-use` — 95%+ health score
- `warming-up` — brand new, first 2 weeks
- `in-recovery` — under 95% health score; change settings + tag

**Daily Campaign Limit:**
- This is the "ultimate throttle" — absolute max per account per day
- Azure: 5 emails/day max
- Google/M365: higher (see Felipe's linked settings page — comment on video to get link)
- Set this low and DO NOT change it unless you know what you're doing

**Minimum Waiting Time Between Emails:**
- Must be 10+ minutes to avoid looking automated
- Formula: (Working hours ÷ emails per day) × 60 = minimum wait in minutes
- Example: 5 emails over 8 hours → wait ~90 min → set to 60 min (safe buffer)
- Higher = better, as long as you hit your daily send target

**Campaign Slow Ramp Up:**
- Only enable if sending MORE than 15 emails/day
- For Azure (5/day), keep OFF

**Reply-To Address:** Leave blank (avoid common denominators pointing back to same place)

**Daily Inbox Placement Tasks:** Set to 0
- Placement tests send to test inboxes, not real prospects
- ESPs learn from real behavior — test inboxes don't reflect your actual ICP
- Only use if you suspect auth issues (SPF/DKIM/DMARC) or full blacklisting

**Custom Tracking Domain:** Disabled
- Used to improve deliverability via link warming
- Felipe saw 20% improvement historically, now sees no difference
- Not worth the setup time currently

**Warm-Up Reply Rate:** 40%+ total
- Mimics natural human email behavior
- Set warm-up reply rate high enough (65–70%) to ensure the 40% total is maintained
- Even a "hated" account gets 30% engagement from real humans
- Formula: (campaign replies + warmup replies) ÷ total sent = must exceed 40%

**Increase Per Day:** Enable ONLY for brand new accounts during initial warm-up
- Ramps from 1→2→3... emails/day gradually
- Shows ESPs growing engagement pattern

---

## Phase 2: Deploying the Army

### Lead List Requirements (Before Upload)

**Two-step validation process:**
1. **Verify** — AI bot visits each website, confirms company matches your ICP (e.g., actual HVAC company, not an HVAC marketing agency)
2. **Validate** — Confirms email address exists and domain has MX records
   - Tools: Lead Magic + Instantly's built-in validation

### Campaign Sequences (Copy Strategy)

**Long copy works best when:**
- Prospect already knows about your type of solution
- You have case studies, social proof, differentiators to show

**Short copy works best when:**
- You just want hand-raisers ("Yes, I'm interested")
- Offer is simple and doesn't need context

**Spin syntax is non-negotiable:**
- Wrap every variable sentence in spin syntax `{option1|option2|option3}`
- Randomizes the email so every send looks unique
- Goal: No common denominator across all campaigns/accounts

### Sending Schedule

- Monday–Friday only (follow CAN-SPAM)
- Business hours only
- Exception: Sunday sends ("hope to catch you before Monday craziness") can perform better but check legality

### Account Selection

- Add ALL accounts to ALL campaigns (where name makes sense)
- Do NOT create account batches or select specific accounts per campaign
- Throttle happens at the account level, not the campaign level
- If you throttle at the email account, adding it to 5 campaigns still only sends 5/day total

### Key Campaign Toggles

| Setting | Felipe's Choice | Reason |
|---------|----------------|--------|
| Stop on reply | ON | Don't re-contact interested leads |
| Open tracking | OFF | Inaccurate since iOS 14; flashy subject lines ≠ more leads |
| Link click tracking | OFF | Track conversions on website (Google Analytics), not clicks |
| Delivery optimization | Text-only | Less HTML = less code ESPs have to scrutinize |
| Auto-optimize | OFF | Qualitative decisions, not quantitative |
| Provider matching | OFF | Irrelevant — recipient end matters, not sender end |
| Stop campaign on company reply | ON | If founder replied, don't hit the COO too |
| Stop on auto-reply | OFF | Let follow-up sequence catch them when they're back |
| Unsubscribe link | OFF (if text-only) | Better performance; add "reply to opt out" line instead |
| Allow risky emails | ON | |
| Bounce protect | OFF (unless catch-all) | |
| BCC | OFF | Use webhooks to CRM instead |

### Sending Pattern

- Min waiting time in campaign = account wait time + 1 minute
- Example: Account waits 60 min → Campaign sending pattern = 61 min
- This ensures the campaign knocks when the account is ready
- Add random wait > 5 min on top of minimum

### Follow-Up Strategy

- **Large TAM (100k+):** Only send email #1, cycle through market monthly with new copy
- **Small TAM:** Full sequence with multiple follow-ups
- Always acknowledge "last email in sequence" — triggers a response bump

### Optimization Hierarchy (Qualitative)

1. Email that generated most **closed deals** (if enough data)
2. Email that generated most **booked appointments**
3. Email that generated most **positive/enthusiastic replies**
4. Email that generated most **positive replies** (any positive)
5. Highest **reply rate** overall

Do NOT let AI auto-optimize. Make decisions yourself, working backward from revenue.

---

## Phase 3: Capturing Intel

- **Analytics** are managed via automation (pulls data into Postgres/Airtable)
- Uni-box: Automation drafts replies based on pattern from past replies
- Instantly's AI inbox agent is good — trains on your reply style
- Look for patterns in emails that are NOT getting replies (that's where the optimization lives)

---

## Phase 4: Refining & Scaling — Automations

Felipe runs everything via **N8N (self-hosted)**:
- Costs $4–$6/month to run millions of records weekly
- Applies account settings automatically
- Reconnects disconnected accounts
- AI-verifies lead lists
- Drafts inbox replies
- Manages split testing data

**Key automation he built:**
- Daily check: reads settings from Postgres DB → applies correct settings to each account → fixes health scores → re-tags accounts

**The point:** At scale, you should never manually touch settings. Build automations once, let them run.

---

## Deliverability Rules of Thumb

1. **Bulk + Unsolicited = Spam Flag** — You need to randomize enough so no common denominator is found
2. **Randomize everything:** signatures, subject lines, copy sentences, links
3. **Text-only emails** are treated more leniently by ESPs
4. **Link randomization:** Use multiple versions of the same URL (`http` vs `https`, different subdomain formats)
5. **Retarget cold email visitors:** Add Facebook Pixel + Google Tag to website, run ads to everyone who visits from cold email

---

## Tools Felipe Uses

| Tool | Purpose |
|------|---------|
| Instantly.ai | Cold email sending platform |
| N8N (self-hosted) | All automations, AI verification, drafts |
| Clay.com | Lead verification/enrichment |
| Lead Magic | Email validation |
| Postgres | Custom CRM + settings database |
| Airtable | CRM alternative |
| HubSpot Enterprise | CRM for large clients |
| Google Analytics | Website engagement tracking |

---

## Cross-Reference: Mbusiness Branding Relevance

**Highly relevant:**
- Felipe targets business owners (similar to Mbusiness Branding targeting home service owners)
- Short copy strategy = perfect fit for Founders VIP Program outreach (simple offer, just want hand-raisers)
- N8N usage matches our existing stack
- The 4-phase framework maps directly to building a cold email outbound for VIP Program

**Actionable for Mbusiness:**
- Use short copy for Founders VIP outreach (just want "yes, I'm interested")
- Verify/validate every lead list before uploading to Instantly
- Stop on reply = always ON (don't spam interested prospects)
- Text-only emails to maximize deliverability
- Diversify email providers from day one
- Add Facebook Pixel + Google Pixel to mbusinessbrandingai.com for retargeting cold leads

**Not immediately relevant:**
- High-volume tactics (20M emails) — we're in early stage
- Advanced N8N automation setup — good for future scaling
- Multiple inbox management at scale
