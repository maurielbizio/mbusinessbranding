# Cold Email Outbound Setup Guide
**Source:** Felipe Fuhr — "Best Instantly AI Settings in 2026"
**Adapted for:** Mbusiness Branding AI — Founders VIP Program Outreach
**Date:** 2026-03-13

---

## What This Guide Is

A step-by-step cold email outbound system for reaching home service business owners and getting them to raise their hand for the Founders VIP Program. Built from Felipe Fuhr's 7-year, 20M+ email framework and adapted for our stage of business.

---

## The 4-Phase System

```
Phase 1 → Build the Base     (domains, inboxes, warm-up)
Phase 2 → Deploy the Army    (leads, copy, campaign logic)
Phase 3 → Capture Intel      (what's working, what's not)
Phase 4 → Refine & Scale     (automations, optimization)
```

---

## Phase 1: Build the Base

### Step 1 — Buy Domains (Never Use Your Main Domain)

- Purchase 2–3 cold outreach domains (variations of your main brand)
- Examples: `mbizbranding.com`, `mbrandingai.com`, `getmbusiness.com`
- Set up SPF, DKIM, and DMARC records on each
- Point MX records so they can receive replies

**Why:** Protects your main domain reputation if anything goes wrong.

### Step 2 — Set Up Email Accounts

- Create 2–3 email accounts per domain (e.g., `mauriel@`, `hello@`, `team@`)
- **Diversify providers:** Use a mix of Google Workspace + Microsoft 365 at minimum
  - Google: $7/mo per inbox, high volume, strong deliverability
  - Microsoft 365: $7/mo per inbox, high volume, strong deliverability
  - Azure: ~$1/mo, low volume (max 5/day), good as a backup

**Why:** If one provider has issues, others keep pipeline flowing. Never have all eggs in one basket.

### Step 3 — Warm Up Each Account

Configure these settings for every new email account in Instantly:

| Setting | Value | Why |
|---------|-------|-----|
| Daily campaign limit | 5 (Azure), 20–30 (Google/M365 after ramp) | Throttle to avoid flags |
| Min wait between sends | 60 minutes | Looks human, not automated |
| Warm-up reply rate | 65–70% | Ensures 40%+ overall engagement |
| Increase per day | ON (new accounts only) | Gradual ramp looks natural |
| Signature | BLANK | Add via spin syntax in copy instead |
| Reply-to address | BLANK | No common denominator |
| Custom tracking domain | OFF | No measurable benefit currently |
| Daily inbox placement | 0 | Inaccurate — don't waste sends |

**Warm-up period:** Minimum 14 days before using in campaigns.

**Account tagging system:**
- `warming-up` → New accounts (0–14 days)
- `ready-to-use` → 95%+ health score, cleared to use
- `in-recovery` → Dropped below 95%; pause campaigns, fix settings

### Step 4 — Deliverability Checklist (Before Any Sending)

- [ ] SPF record set up on each domain
- [ ] DKIM record set up on each domain
- [ ] DMARC record set up on each domain
- [ ] MX records pointing correctly
- [ ] Email accounts warmed up for 14+ days
- [ ] Health score 95%+ in Instantly
- [ ] No active spam complaints on domain (check MX Toolbox)

---

## Phase 2: Deploy the Army

### Step 5 — Build a Clean Lead List

Never upload a raw lead list. Every list must go through 2 gates:

**Gate 1 — Verify (ICP match):**
- AI or manual check: Does this company actually match your ICP?
- For Mbusiness: Is this an actual home service business (HVAC, roofing, cleaning, landscaping, etc.) — NOT a marketing company or consultant?
- Tools: Clay.com (recommended), N8N + AI automation

**Gate 2 — Validate (Email exists):**
- Check that the email address is real and the domain has MX records
- Tools: Lead Magic, Instantly built-in validation, NeverBounce
- Remove all invalid, catch-all (unless you bounce-protect), and role emails (info@, contact@)

**Target list for Mbusiness Founders VIP:**
- HVAC companies (5–20 employees)
- Roofing companies (5–20 employees)
- Landscaping/lawn care companies
- House cleaning/maid services
- General contractors
- Any home service business that advertises locally but has no online booking

### Step 6 — Write the Copy

**For Founders VIP Program: Use SHORT copy.**

The offer is simple. You just need them to raise their hand. Don't over-explain.

**Short copy formula:**
```
Line 1: Call out their specific problem
Line 2: One-line statement of what you do
Line 3: Social proof (brief)
Line 4: One simple CTA
```

**Example:**
```
Subject: {Quick question for you|One thing I noticed|{firstName}, real quick}

Hey {firstName},

Most {industry} businesses I talk to are losing leads at night and on weekends
because there's no one to answer the phone.

We build AI receptionists that handle inquiries and book appointments 24/7 —
so you stop losing jobs while you're working or sleeping.

We're launching a Founders program for {number} businesses — 75% off first month.

Worth a 15-minute call to see if it's a fit?

{Felipe|Hey|Cheers},
{Mauriel|M.|The team at Mbusiness}
```

**Spin syntax rules:**
- Wrap every variable phrase: `{option A|option B|option C}`
- Randomize the greeting, sign-off, subject line, and at least 2 body sentences
- Goal: No two emails look identical

### Step 7 — Configure the Campaign

**Accounts to use:** Select ALL ready-to-use accounts. Don't create batches.
The throttle is at the account level. Adding one account to 5 campaigns still only sends 5/day.

**Campaign settings:**

| Setting | Value | Why |
|---------|-------|-----|
| Stop on reply | ON | Don't re-contact interested leads |
| Stop campaign for company on reply | ON | If owner replied, don't hit their manager too |
| Open tracking | OFF | Inaccurate + hurts lead rate |
| Link click tracking | OFF | Track on your website instead |
| Delivery optimization | Text-only | Less HTML = less ESP scrutiny |
| Auto-optimize | OFF | Make decisions yourself, qualitatively |
| Provider matching | OFF | Doesn't matter — recipient end is what counts |
| Stop on auto-reply | OFF | Let next follow-up catch them |
| Unsubscribe link | OFF (text-only) | Add "reply to opt out" line instead |
| Allow risky emails | ON | |
| Bounce protect | OFF (turn ON for catch-all lists) | |
| BCC to CRM | OFF | Use webhook instead |
| Sending pattern min wait | Account wait + 1 min | Ensures account is always ready when campaign knocks |
| Random wait addition | 5–15 min | Natural variability |
| Daily limit override | Keep HIGH (5,000+) | Real throttle is at account level |

**Sequence strategy for Founders VIP:**
- Email 1: Short pitch (as above)
- Email 2 (5–7 days later): Different angle, same offer
- Email 3 (7 days later): "Last chance" / "closing this out" — drives reply bump
- Keep to 3 emails max at this stage

**Schedule:** Monday–Friday, 9am–5pm local time

---

## Phase 3: Capture Intel

### Step 8 — Know What to Measure

Do NOT track opens. Do NOT track link clicks. Track these instead:

| Metric | What It Tells You |
|--------|------------------|
| Reply rate | How resonant is your copy + targeting |
| Positive reply rate | How qualified your leads are |
| Booked calls | Actual pipeline created |
| Closed deals | Revenue generated |

**Optimization hierarchy** (work backward from revenue):
1. Which email generated the most **closed deals**?
2. Which email generated the most **booked calls**?
3. Which email generated the most **enthusiastic replies**?
4. Which email generated the most **any positive replies**?
5. Which email had the best **reply rate**?

Start with step 5 (you won't have enough data initially). As you send more, move up the chain.

### Step 9 — Split Test Everything

- Run 2–3 copy variants at all times
- Test subject lines separately from body copy
- Test short vs. long CTA
- Never let AI auto-optimize (you need qualitative judgment, not just reply count)
- Keep notes on what you learn each week

### Step 10 — Watch for These Warning Signs

| Warning | Action |
|---------|--------|
| Health score drops below 95% | Move account to `in-recovery`, pause from campaigns, fix settings |
| Bounce rate spiking | Clean list again, check validation |
| Zero replies after 100+ sends | Problem is likely copy or targeting, not deliverability |
| Spam complaints coming in | Slow down, check copy for overly salesy language, verify list quality |

---

## Phase 4: Refine & Scale

### Step 11 — Add Retargeting (High ROI, Easy Win)

Anyone who opens your cold email and visits your website should get retargeted with ads.

**Setup:**
1. Install Facebook Pixel on mbusinessbrandingai.com
2. Install Google Tag (GA4) on mbusinessbrandingai.com
3. Create custom audiences from website visitors
4. Run retargeting ads to this audience on Facebook/Instagram

**Why this matters:** Cold email creates awareness. Ads re-engage the maybe's. The combination converts at a much higher rate than either channel alone.

### Step 12 — Build Automations (When Volume Justifies It)

Once you're sending 500+ emails/day, manual management becomes a bottleneck. Automate:

- Daily health score check → auto-tag accounts (ready/recovery/warming)
- Auto-reconnect disconnected accounts
- AI inbox replies drafted based on your past reply patterns
- Lead list verification (AI visits website before adding to campaign)

**Tool:** N8N (self-hosted at ~$4–6/mo) or N8N cloud
**Priority order:** Start manual, build automations when volume demands it.

---

## Quick Reference: Deliverability Rules

1. **Never send from your main domain** — use cold outreach variants
2. **Diversify email providers** — Google + Microsoft + Azure at minimum
3. **Text-only emails** — no images, no HTML formatting, no fancy signatures
4. **Spin syntax on every email** — randomize everything possible
5. **60+ minute gaps between sends** — never looks automated
6. **14-day warm-up minimum** before using accounts in campaigns
7. **Clean list first** — verify ICP + validate emails before importing
8. **Max 5 sends/day for Azure, 20–30 for Google/M365 (during ramp)**
9. **Always stop on reply** — never re-contact an interested lead
10. **Monitor health scores daily** — 95%+ to stay in rotation

---

## Mbusiness Branding: 30-Day Cold Email Launch Checklist

**Week 1: Infrastructure**
- [ ] Buy 2–3 outreach domains
- [ ] Set up SPF/DKIM/DMARC on each
- [ ] Create 2 inboxes per domain (Google + Microsoft)
- [ ] Connect to Instantly, begin warm-up
- [ ] Install Facebook Pixel + Google Analytics on site

**Week 2: List Building**
- [ ] Define ICP criteria for home service businesses
- [ ] Build first lead list (250–500 verified contacts)
- [ ] Run verification (ICP match check)
- [ ] Run validation (email exists check)
- [ ] Import clean list to Instantly

**Week 3: Copy & Campaign**
- [ ] Write 2 short copy variants for Founders VIP
- [ ] Add spin syntax to both
- [ ] Configure campaign with correct settings (use guide above)
- [ ] Set up 3-email sequence
- [ ] Launch with accounts that hit 95%+ health score

**Week 4: Measure & Optimize**
- [ ] Track reply rates per variant
- [ ] Identify winning copy
- [ ] Check deliverability health (bounce rate, spam flags)
- [ ] Adjust based on qualitative feedback from replies
- [ ] Plan list expansion for Month 2

---

## Key Insight for Mbusiness Branding

Felipe's whole system is built around one idea: **remove common denominators.**

Every email must look unique. Every account must behave differently. Every link must vary. This is how you send at scale without getting flagged — not by volume tricks, but by genuine variability that mimics human behavior.

For Mbusiness Branding at early stage: apply the same discipline even at low volume. Building good habits from day one is 10x easier than fixing bad deliverability later.

---

*Last updated: 2026-03-13*
*Source video: https://www.youtube.com/watch?v=jTKNZt_i5c0*
