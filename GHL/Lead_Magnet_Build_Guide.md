# GHL Manual Build Guide — 14-Day Social Content Lead Magnet Funnel

**Complete this guide in order. Items marked ✅ are already built.**

---

## What's Already Built (via MCP)

| Item | Status | Notes |
|------|--------|-------|
| 17 tags | ✅ Done | See CLAUDE.md for full tag list |
| Custom Field: Content Drive Link | ✅ Done | `contact.content_drive_link` |
| Custom Value: Google Review Link | ✅ Done | `{{ custom_values.google_review_link }}` = `https://share.google/JKB6WUwaHsH03osQa` |
| Email content (all 4 WFs) | ✅ Done | Written inline into each workflow "Send Email" step — use AI prompts from **GHL AI Prompts** section or paste from **Email & SMS Copy Reference** section |

> **Note:** There are no email templates in GHL for this funnel. All email content goes directly into workflow email steps using the inline editor. This is the reliable approach — templates in GHL do not save HTML content via API.

### Trigger Links (use these everywhere — they track clicks)
| Purpose | GHL Syntax |
|---------|------------|
| Intake form link | `{{trigger_link.qc2JrP2d9ldX7mbnHJBR}}` |
| Strategy call booking | `{{trigger_link.h1zRc64nntbXDFn4F8Wg}}` |
| Google My Business review | `{{trigger_link.C8Pn0tjbfIk2QdT93flc}}` |

---

## Step 1 — Create the Lead Magnet Pipeline

**Path:** Opportunities > Pipelines > + Add Pipeline

**Pipeline name:** `Lead Magnet Funnel`

Add these stages in order:
1. Opted In
2. Intake Received
3. Content In Progress
4. Content Delivered
5. VIP Pitch Active
6. Call Booked
7. Converted
8. Ghosted

Save.

---

## Step 2 — Create the Opt-In Form (Already Live)

Form is live as a popup at: `https://links.mbusinessbrandingai.com/widget/form/2EIXBphuh9E7jXraVQuc`

Paste this URL into the `[INTAKE_FORM_LINK]` replacements in Step above and in workflow SMS steps.

---

## Step 3 — Create the Intake Form (Detailed)

**Path:** Sites > Forms > + New Form

**Form name:** `LM Intake — Business Info`

**Fields to add:**
1. First Name + Last Name (pre-fill)
2. Email (pre-fill)
3. Business Type / Trade — dropdown:
   - Plumbing, HVAC, Roofing, Landscaping / Lawn, Electrical, Painting, Cleaning, General Contracting, Real Estate Agent, Pet Grooming, Barber/Hair Stylist, Automotive, Medical/Wellness, Law Firm, Dentist, Chiropractors, Other
4. Service Area (cities/zip codes you serve)
5. Years in Business
6. What makes your business different from competitors?
7. What is your biggest challenge right now? — dropdown: Getting more leads / Getting more reviews / Staying consistent online / Other
8. Google Business Profile link (optional)
9. Current social media links (optional)
10. Best time to reach you

**On Submit message:** *"Perfect! I am building your content now. You will hear from me within a few business days."*

After saving — copy the form URL and replace `{{trigger_link.qc2JrP2d9ldX7mbnHJBR}}` usage in workflows with this URL if you prefer not to use the trigger link. (Trigger link is recommended — it tracks clicks.)

---

## Step 4 — Build Workflow 1: LM-01 | Opt-In Received

**Path:** Automation > Workflows > + New Workflow > Start from Scratch

**Name:** `LM-01 | Opt-In Received`

**Trigger:** Form Submitted → select `LM Opt-In — 14-Day Social Content` (the popup form)

**Steps:**

1. **Create/Update Contact** — map: first name, last name, email, phone, company name, city
2. **Add Contact Tag** → `lm-optin`, `lm-intake-sent`
3. **Add to Opportunity** → Pipeline: Lead Magnet Funnel → Stage: Opted In → Status: Open
4. **Send Email** → click **+ Send Email** → choose **Custom Email** → use the inline editor:
   - **Subject:** `Your 14-day social media content — one quick step`
   - **Body:** Click the AI button and paste this prompt:
     > Write a plain-text confirmation email for a small business owner who just signed up for 14 days of free AI-generated social media content from Mbusiness Branding AI. Tell them their content is being built, that they need to complete a quick intake form so the content can be personalized to their business, and include this link: {{trigger_link.qc2JrP2d9ldX7mbnHJBR}}. Tone: confident, direct, no fluff. Sign off as Mauriel, Mbusiness Branding AI. Length: 100–150 words.
5. **Wait** → 10 minutes
6. **Send SMS** → paste SMS 1:
   > Hey {{contact.first_name}}, it's Mauriel. You just signed up for the free 14-day social media content. Perfect! I need 3 mins of info so I can build it for your business. Fill this out: {{trigger_link.qc2JrP2d9ldX7mbnHJBR}}
7. **Wait** → 24 hours
8. **If/Else** → Condition: Contact Tag → contains → `lm-intake-completed`
   - **YES** → End Workflow
   - **NO** → continue:
9. **Send SMS** → paste SMS 2:
   > Hey {{contact.first_name}} — still have your free 14-day content ready to build. Just need your intake form. 3 minutes: {{trigger_link.qc2JrP2d9ldX7mbnHJBR}}. No form = no content, so let me know either way.
10. **Wait** → 24 hours
11. **If/Else** → same condition `lm-intake-completed`
    - **YES** → End Workflow
    - **NO** → continue:
12. **Send Email** → click **+ Send Email** → choose **Custom Email** → use the inline editor:
    - **Subject:** `Are you still in?`
    - **Body:** Click the AI button and paste this prompt:
      > Write a plain-text follow-up email for a small business owner who signed up for free social media content but hasn't completed their intake form yet. The content can't be built without the form. Keep it casual and low-pressure — remind them their spot is still open and include this intake form link: {{trigger_link.qc2JrP2d9ldX7mbnHJBR}}. Tone: direct, no guilt, no fluff. Sign off as Mauriel, Mbusiness Branding AI. Length: 80–120 words.
13. **Add Contact Tag** → `lm-no-intake`
14. **Wait** → 48 hours
15. **If/Else** → `lm-intake-completed`
    - **YES** → Remove tag `lm-no-intake` → End Workflow
    - **NO** → Update Opportunity Stage → Ghosted → End Workflow

**Save & Publish.**

---

## Step 5 — Build Workflow 2: LM-02 | Intake Form Received

**Path:** Automation > Workflows > + New Workflow > Start from Scratch

**Name:** `LM-02 | Intake Form Received`

**Trigger:** Form Submitted → select `LM Intake — Business Info`

**Steps:**

1. **Remove Contact Tag** → `lm-no-intake`, `lm-intake-sent`
2. **Add Contact Tag** → `lm-intake-completed`, `lm-content-building`
3. **Update Opportunity Stage** → Lead Magnet Funnel → Intake Received
4. **Send Email** → `LM-Email-03 | Intake received — I am on it`
5. **Send Internal Notification** → Email to yourself:
   - Subject: `NEW INTAKE — {{contact.company_name}} needs content built`
   - Body: `Name: {{contact.full_name}} | Business: {{contact.company_name}} | Type: {{contact.business_type}} | Email: {{contact.email}} | Phone: {{contact.phone}} | City: {{contact.city}} — Go to GHL and add tag lm-content-ready once Drive link is pasted on their contact record.`
6. **Update Opportunity Stage** → Content In Progress
7. **Wait** → 72 hours
8. **If/Else** → Tag contains `lm-content-ready`
   - **YES** → continue to step 9
   - **NO** → Send another internal notification reminder → Wait 24 hours → If/Else again → if still NO → End Workflow
9. **Remove Contact Tag** → `lm-content-building`
10. **Add Contact Tag** → `lm-start-delivery` *(this fires Workflow 3)*
11. **End Workflow**

**Save & Publish.**

---

## Step 6 — Build Workflow 3: LM-03 | 14-Day Delivery + Nurture

**Path:** Automation > Workflows > + New Workflow > Start from Scratch

**Name:** `LM-03 | 14-Day Delivery + Nurture`

**Trigger:** Contact Tag Added → `lm-start-delivery`

---

### — DAY 0 (Generic for all business types) —

1. **Remove Contact Tag** → `lm-start-delivery`
2. **Update Opportunity Stage** → Content Delivered
3. **Send Email** → `LM-Email-04 | Your Week 1 content is here — ready to post`
   - Uses `{{contact.content_drive_link}}` — make sure Drive link is on contact record before adding `lm-content-ready` tag
4. **Add Contact Tag** → `lm-week1-sent`
5. **Wait** → 30 minutes
6. **Send SMS** → SMS 3 (generic):
   > {{contact.first_name}}, your Week 1 content just hit your inbox. 7 posts, ready to copy and post. Let me know if you have any questions.

---

### — BUSINESS TYPE BRANCH (add immediately after SMS 3) —

Add one **If/Else** that checks the **Business Type / Trade** contact field:

> **Branch 1:** Business Type = Plumbing → run **Plumbing Sequence** below
> **Branch 2:** Business Type = HVAC → run **HVAC Sequence** below
> **Branch 3:** Business Type = Roofing → run **Roofing Sequence** below
> **Branch 4:** Business Type = Landscaping / Lawn → run **Landscaping Sequence** below
> **Branch 5:** Business Type = Cleaning → run **Cleaning Sequence** below
> **Branch 6:** Business Type = General Contracting → run **General Contracting Sequence** below
> **Branch 7:** Business Type = Barber/Hair Stylist → run **Barber Sequence** below
> **Branch 8:** Business Type = Automotive → run **Automotive Sequence** below
> **Else (default):** run generic sequence using existing LM-Email-05 through 09

Each branch follows the **same timing and logic** — only the email template and SMS copy changes.

---

### BRANCH SEQUENCE TEMPLATE (repeat for each of the 8 types, swap [TYPE] for business name)

> **How to add email content in each Send Email step:**
> Click **+ Send Email** → choose **Custom Email** → use the inline editor.
> **Option A (faster):** Click the AI magic wand button → paste the prompt from the **GHL AI Prompts** section below → generate → review and adjust.
> **Option B (manual):** Copy the full email body from the **Email & SMS Copy Reference** section below.

**— DAY 3 —**
- **Wait** → 2 days 23 hours 30 minutes (from SMS 3)
- **Send Email** → Custom Email → Subject and body from **Email-05-[TYPE]** (see GHL AI Prompts or Email & SMS Copy Reference)

**— DAY 5 —**
- **Wait** → 2 days
- **Send SMS** → SMS 4-[TYPE] (see SMS Copy section below)

**— DAY 7 —**
- **Wait** → 2 days
- **Send Email** → Custom Email → Subject and body from **Email-06-[TYPE]** (see GHL AI Prompts or Email & SMS Copy Reference)
- **Add Contact Tag** → `lm-week2-sent`, `lm-vip-offer-sent`, `founders-vip-prospect`
- **Update Opportunity Stage** → VIP Pitch Active
- **Wait** → 30 minutes
- **Send SMS** → SMS 5-[TYPE] (see SMS Copy section below)

**— DAY 9 —**
- **Wait** → 2 days
- **If/Else** → Tag contains `lm-booked-call`
  - **YES** → Go To Day 17 step
  - **NO** → **Send Email** → Custom Email → Subject and body from **Email-07-[TYPE]** (see GHL AI Prompts or Email & SMS Copy Reference)

**— DAY 11 —**
- **Wait** → 2 days
- **If/Else** → Tag contains `lm-booked-call`
  - **YES** → Go To Day 17 step
  - **NO** → continue:
  - **Send Email** → Custom Email → Subject and body from **Email-08-[TYPE]** (see GHL AI Prompts or Email & SMS Copy Reference)
  - **Send SMS** → SMS 6-[TYPE] (see SMS Copy section below)

**— DAY 14 —**
- **Wait** → 3 days
- **If/Else** → Tag contains `lm-booked-call`
  - **YES** → Go To Day 17 step
  - **NO** → continue:
  - **Send Email** → Custom Email → Subject and body from **Email-09-[TYPE]** (see GHL AI Prompts or Email & SMS Copy Reference)
  - **Send SMS** → SMS 7-[TYPE] (see SMS Copy section below)

**— DAY 17 —**
- **Wait** → 1 day
- **Add Contact Tag** → `lm-sequence-complete`
- **If/Else** → Tag contains `lm-booked-call` OR `founders-vip-client`
  - **YES** → Remove tag `founders-vip-prospect` → End Workflow
  - **NO** → Update Opportunity Stage → Ghosted → Add tag `lm-unresponsive` → End Workflow

**Save & Publish.**

---

## SMS Copy — All 8 Business Types

### SMS 4 (Day 5 — check-in)

**Plumbing:**
> {{contact.first_name}} — have you had a chance to post any of the content yet? Even one post can bring in a call. Reply and let me know how it's going.

**HVAC:**
> {{contact.first_name}} — how's the Week 1 content working for you? HVAC season is coming — now's the time to get visible. Let me know if you have questions.

**Roofing:**
> {{contact.first_name}} — have you posted any of the content yet? A consistent presence is what separates you from the next roofer on Google. Let me know how it's going.

**Landscaping/Lawn:**
> {{contact.first_name}} — spring is coming and homeowners are starting to look. Have you posted any content yet? Reply and let me know how it's going.

**Cleaning:**
> {{contact.first_name}} — have you posted any of the content yet? Every post builds the trust new clients need before they call. Let me know how it's going.

**General Contracting:**
> {{contact.first_name}} — have you had a chance to post any of the content? Before/after posts are gold for contractors. Let me know how it's going.

**Barber/Hair Stylist:**
> {{contact.first_name}} — have you posted any transformation content yet? Those posts bring in new clients. Reply and let me know how it's going.

**Automotive:**
> {{contact.first_name}} — have you posted any content yet? Trust-building posts are how you pull customers away from the chains. Let me know how it's going.

---

### SMS 5 (Day 7 — after VIP email sent, 30 min delay)

**Plumbing:**
> {{contact.first_name}}, Week 2 content is in your inbox. Also sent you details on the Founders VIP Program — AI answering your calls while you're on jobs. 10 spots, 75% off first month. Worth a read.

**HVAC:**
> {{contact.first_name}}, Week 2 content is in your inbox. Also sent you the Founders VIP details — built for HVAC businesses, 10 spots, 75% off first month. Worth a read before peak season.

**Roofing:**
> {{contact.first_name}}, Week 2 content is ready. Also sent you Founders VIP details — automated follow-up for your estimates, 10 spots, 75% off first month. Worth a look.

**Landscaping/Lawn:**
> {{contact.first_name}}, Week 2 content is in your inbox. Also sent you details on the Founders VIP Program — built to fill your spring schedule. 10 spots, 75% off first month.

**Cleaning:**
> {{contact.first_name}}, Week 2 content is ready. Also sent you Founders VIP details — built to turn one-time clients into recurring. 10 spots, 75% off first month.

**General Contracting:**
> {{contact.first_name}}, Week 2 content is in your inbox. Also sent you the Founders VIP details — keeps your pipeline running while you're on a job. 10 spots, 75% off first month.

**Barber/Hair Stylist:**
> {{contact.first_name}}, Week 2 content is ready. Also sent you Founders VIP details — cuts no-shows and keeps your book full. 10 spots, 75% off first month.

**Automotive:**
> {{contact.first_name}}, Week 2 content is in your inbox. Also sent you Founders VIP details — maintenance reminders and review automation to beat the chains. 10 spots, 75% off.

---

### SMS 6 (Day 11 — urgency)

**Plumbing:**
> {{contact.first_name}} — a few Founders VIP spots are already taken. Wanted to make sure you saw the update I just emailed you. Happy to answer questions here too.

**HVAC:**
> {{contact.first_name}} — a few HVAC spots in the Founders Program are claimed. Check the email I just sent you. Questions? Reply here.

**Roofing:**
> {{contact.first_name}} — Founders VIP spots are going. Check the email I just sent for the update. Questions? Just reply.

**Landscaping/Lawn:**
> {{contact.first_name}} — Founders spots are filling before spring. Check the email I just sent. Questions? Reply here anytime.

**Cleaning:**
> {{contact.first_name}} — a few Founders spots are claimed. Check the update I just emailed you. Happy to answer questions here.

**General Contracting:**
> {{contact.first_name}} — Founders VIP spots are going. Sent you an update. Questions? Reply here.

**Barber/Hair Stylist:**
> {{contact.first_name}} — a few shop owner spots in the Founders Program are taken. Check the email I just sent. Questions? Reply here.

**Automotive:**
> {{contact.first_name}} — Founders VIP spots are going. Check the update I just emailed. Questions? Just reply here.

---

### SMS 7 (Day 14 — last call)

**Plumbing:**
> {{contact.first_name}}, last email on the Founders VIP offer just went out. If you want to talk before spots fill, book here: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure either way.

**HVAC:**
> {{contact.first_name}}, last email on the Founders VIP offer just went out. Want to talk before peak season? Book here: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

**Roofing:**
> {{contact.first_name}}, last email on the Founders VIP offer. Book a quick call if you want to talk before spots close: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

**Landscaping/Lawn:**
> {{contact.first_name}}, last email on the Founders offer. Book before spots fill: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure either way.

**Cleaning:**
> {{contact.first_name}}, last email on the Founders VIP offer. Book a call if you want to talk: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

**General Contracting:**
> {{contact.first_name}}, last email on Founders VIP. Want to talk before spots close? Book here: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

**Barber/Hair Stylist:**
> {{contact.first_name}}, last email on the Founders offer. Book a call if you want to talk about your shop: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

**Automotive:**
> {{contact.first_name}}, last email on Founders VIP. Book a call before spots close: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. No pressure.

---

## Step 7 — Build Workflow 4: LM-04 | Google Review Request

**Path:** Automation > Workflows > + New Workflow > Start from Scratch

**Name:** `LM-04 | Google Review Request`

**Trigger:** Contact Tag Added → `lm-week2-sent`

**Steps:**

1. **Wait** → 3 days
2. **If/Else** → Tag contains `review-completed`
   - **YES** → End Workflow
   - **NO** → continue:
3. **Send SMS** → SMS 8:
   > Hey {{contact.first_name}}, it's Mauriel. You've had your 14-day content for a bit. If it was helpful, would you mind leaving a quick Google review? 30 seconds: {{trigger_link.C8Pn0tjbfIk2QdT93flc}}. Thanks either way.
4. **Add Contact Tag** → `review-requested`
5. **Wait** → 48 hours
6. **If/Else** → Tag contains `review-completed`
   - **YES** → End Workflow
   - **NO** → continue:
7. **Send Email** → Custom Email → inline editor:
   - **Subject:** `Quick favor — takes 30 seconds`
   - **Body:** Click the AI button and paste this prompt:
     > Write a plain-text email asking a small business owner for a Google review. They received 14 days of free AI social media content from Mbusiness Branding AI. Keep it brief and low-pressure. Include this review link: {{trigger_link.C8Pn0tjbfIk2QdT93flc}}. Tone: casual, appreciative, no fluff. Sign off as Mauriel, Mbusiness Branding AI. Length: 80–100 words.
8. **Wait** → 5 days
9. **End Workflow**

**Save & Publish.**

---

## Step 8 — Update Existing Reputation Workflow

**Path:** Automation > Workflows → open your existing reputation management workflow

**Add at the very start (before any actions):**
- If/Else → Contact Tag → contains → `lm-optin`
  - **YES** → End Workflow (WF-04 handles these contacts)
  - **NO** → continue with existing workflow

**Save & Publish.**

---

## Step 9 — Final Link Replacements Checklist

All trigger links should already be in place. Do a final pass:

| Item | Status |
|------|--------|
| LM-Email-01 → intake trigger link | ⬜ Manual update in GHL UI |
| LM-Email-02 → intake trigger link | ⬜ Manual update in GHL UI |
| LM-Email-10 → Google review trigger link | ⬜ Manual update in GHL UI |
| All WF-01 SMS → intake trigger link | ⬜ Add when building WF-01 |
| All WF-03 SMS 7 → strategy call trigger link | ⬜ Add from SMS copy above |
| WF-04 SMS → Google review trigger link | ⬜ Add when building WF-04 |

---

## Step 10 — Testing Checklist

Run a test contact through the full funnel before going live:

- [ ] Submit opt-in form → verify contact created, 2 tags applied, pipeline stage = Opted In
- [ ] Verify Email-01 arrives, SMS-1 arrives 10 min later (check intake trigger link is correct)
- [ ] Do NOT submit intake form → wait 24h → verify SMS-2 arrives, `lm-no-intake` added at 48hr
- [ ] Submit intake form with business type = Plumbing → verify tags applied, Email-03 arrives, internal notification sent
- [ ] Manually add `lm-content-ready` tag + paste Drive link → verify `lm-start-delivery` fires
- [ ] Verify Email-04 arrives with Drive link, SMS-3 arrives 30 min later
- [ ] Verify WF-03 routes to Plumbing branch (not generic)
- [ ] Verify Email-05-Plumbing arrives on Day 3
- [ ] Verify Email-06-Plumbing arrives on Day 7 with correct strategy call link
- [ ] Add `lm-booked-call` tag manually → verify Day 9/11/14 pitch emails are skipped
- [ ] Verify WF-04 fires (Google review SMS) 3 days after `lm-week2-sent`
- [ ] Verify `review-completed` tag stops WF-04 correctly
- [ ] Test a second contact with business type = HVAC — verify correct branch fires

---

## Owner Workflow (What You Do Per Lead)

1. **New opt-in alert arrives** → check GHL, contact is in "Opted In" stage
2. **Intake form received** → you get internal email notification → build their 14-day content in Google Drive
3. **Content ready** → go to the contact in GHL → paste their Google Drive link into the **Content Drive Link** field → add tag `lm-content-ready` → Workflow 3 fires automatically
4. **Monitor pipeline** → move contacts who book calls to Call Booked stage
5. **Review received** → manually add tag `review-completed` on the contact to stop review follow-up

---

---

## GHL AI Prompts — WF-03 Email Steps

> **How to use:** In each workflow "Send Email" step, click **Custom Email** → open the inline editor → click the **AI (magic wand) button** → paste the prompt below → generate → review the output, adjust subject line if needed, then save.
>
> The prompts are organized by business type. Find the branch you're building and copy the prompt for each day.

---

### PLUMBING — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a plumber who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: They're missing calls while on jobs — a competitor with AI answering is booking those leads instead. Agitate the cost of missed calls. Show what changes when an AI receptionist handles every call 24/7. CTA: Book a free strategy call at {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The plumber down the street just booked your next job". Tone: confident, direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a plumber announcing their Week 2 social media content is ready. Transition into pitching the Founders VIP Program (AI receptionist + reputation management + social posting, 75% off first month, only 10 founding spots). Connect the offer to their pain: missed calls while on jobs. CTA: Book a free strategy call at {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler email for a plumber who hasn't booked a strategy call yet. Handle the most common objection: "I'm too busy / I'll think about it." Reframe: the busiest plumbers are the ones losing the most calls because they can't answer. Show that the system handles the busy season FOR them. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The busiest plumbers I know said the exact same thing". Tone: empathetic but direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a plumber about the Founders VIP Program. A few spots are already claimed. Give them a clear picture of what the VIP spot includes (AI receptionist, reputation management, social posting) and what they'll lose if they wait. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a plumber. This is the final email about the Founders VIP offer. Keep it short. No hard sell — just a low-pressure close. Acknowledge they may not be ready and that's okay. Remind them what's available and include the booking link. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: confident, direct, zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### HVAC — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for an HVAC business owner who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: During peak season they're too busy to answer calls — customers call 3 HVAC companies and book whoever responds in under 5 minutes. Agitate the cost of losing peak-season leads. Show what changes with AI handling every inbound call and booking maintenance visits during the slow season. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Your competitor answered in 4 minutes. You didn't." Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for an HVAC owner announcing their Week 2 content is ready. Transition into pitching the Founders VIP Program (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: peak season call chaos and slow-season booking gaps. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler email for an HVAC owner who hasn't booked a call. Handle: "I'm too busy during the season / I'll deal with it in the off-season." Reframe: the off-season is the perfect time to set the system up so it runs during the next peak. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The best time to set this up is before peak season hits". Tone: empathetic, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for an HVAC owner about Founders VIP spots being claimed. Keep it specific to their trade — summer/winter rush is coming and setting this up now means they don't miss another peak season lead. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few HVAC spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for an HVAC owner. Final email about Founders VIP. Short, direct, no pressure. Acknowledge they may not be ready — that's okay. Remind them what the offer includes, give the link. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: confident, zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### ROOFING — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a roofer who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: They spend time on free estimates that go cold — no automated follow-up system. Competitors follow up on day 2, 5, and 10 and win the job. Agitate the cost of a dead estimate pipeline. Show what changes with automated follow-up and review collection building trust vs fly-by-night competitors. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Why your estimates keep going cold". Tone: direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a roofer announcing their Week 2 content is ready. Transition into pitching the Founders VIP Program (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: estimate follow-up failure and review deficit vs competitors. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler email for a roofer. Handle: "I get most of my work from referrals / I don't need this." Reframe: referrals dry up the moment you get busy and stop following up. AI handles follow-up automatically so the pipeline never goes cold. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Referrals are great — until they stop". Tone: direct, empathetic. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a roofer about Founders VIP spots being claimed. Storm season or busy season is coming — getting set up now means every estimate gets followed up automatically. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a roofer. Final Founders VIP email. Short, no pressure. Give the link and let them decide. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: confident, zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### LANDSCAPING / LAWN — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a lawn and landscaping business owner who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: Seasonal income drops because they have no system keeping them top of mind during winter — clients go with whoever sends a spring postcard first. The lawn company with 50 Google reviews gets the call, not them. Show what changes with year-round content + AI booking spring signups automatically. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The lawn company with 50 reviews gets the call. Not you." Tone: direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a landscaping owner announcing Week 2 content is ready. Transition into pitching Founders VIP (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: being forgotten in the off-season, losing spring signups to competitors who show up consistently. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler for a landscaping owner. Handle: "I'm too busy in season to deal with this." Reframe: the system runs in the background — AI books spring clients during winter without them doing anything. The best time to set it up is before the spring rush. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Set it up once and let it fill your spring schedule". Tone: direct, empathetic. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a landscaping owner about Founders VIP spots being claimed. Spring signups happen now — waiting means another season of manually chasing clients. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a landscaping owner. Final Founders VIP email. Short, no pressure. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: zero pressure, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### CLEANING — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a cleaning business owner who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: Price shoppers, one-time clients who never rebook, and losing to companies with 100+ Google reviews. When someone Googles "cleaning near me," they pick whoever has the most reviews. Show what changes with automated review requests and rebooking follow-ups turning one-time clients into recurring. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Why they booked the company with 87 reviews instead of you". Tone: direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a cleaning business owner announcing Week 2 content is ready. Transition into pitching Founders VIP (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: the review gap and one-time client problem. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler for a cleaning business owner. Handle: "I already have enough clients / word of mouth is fine." Reframe: when a client stops booking, there's no automated system to win them back — word of mouth isn't predictable. Show what consistent follow-up and review collection does for retention and trust. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Word of mouth is great — until it isn't". Tone: direct, empathetic. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a cleaning business owner. A few Founders VIP spots are claimed. Connect urgency to their trade: every week without a review system is another week competitors pull ahead on Google. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a cleaning business owner. Final Founders VIP email. Short, no pressure. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### GENERAL CONTRACTING — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a general contractor who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: Estimates go cold when they're buried in a big job and can't answer calls. Homeowners scroll Instagram and call whoever has before/after content — they never get a chance if they're invisible online. Show what changes with consistent content + AI receptionist keeping the pipeline full even when they're on a project. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "They found a contractor on Instagram. You were still waiting on your estimate." Tone: direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a general contractor announcing Week 2 content is ready. Transition into pitching Founders VIP (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: pipeline going quiet when they're on a big job. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler for a general contractor. Handle: "I stay booked through referrals / I don't have time to set this up." Reframe: referrals dry up when they're heads-down on a long job. The system is set up once — it keeps the pipeline moving without them touching it. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The contractors I know who stay booked do this one thing". Tone: direct, empathetic. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a general contractor. Founders VIP spots are going. Paint the picture: when a big job wraps up and the pipeline is empty, that's the worst time to start building visibility — do it now. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a general contractor. Final Founders VIP email. Short, no pressure. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### BARBER / HAIR STYLIST — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for a barber or hair stylist who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: No-shows kill the day's revenue, and clients drift away after missing a few weeks. The shop down the street texts automated reminders and rebooking links — theirs don't. Show what changes when AI sends reminders, cuts no-shows, and auto-texts "ready to book again?" after 3 weeks of silence. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "The barbershop down the street just kept your client". Tone: direct, conversational. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for a barber/hair stylist announcing Week 2 content is ready. Transition into pitching Founders VIP (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: no-shows and clients who ghost after missing a few weeks. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler for a barber or hair stylist. Handle: "I stay busy / my clients know where to find me." Reframe: their best clients are being pulled away by shops that reach out proactively — automated rebooking texts and reminders keep clients loyal without any manual work. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Your regulars aren't as loyal as you think". Tone: direct, respectful. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for a barber/hair stylist. Founders VIP spots are going. Connect urgency to their trade: every no-show that isn't followed up is money left on the table, and every client who drifts is revenue that compound over time. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for a barber or hair stylist. Final Founders VIP email. Short, no pressure. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

### AUTOMOTIVE — AI Prompts

**Email-05 (Day 3):**
```
Write a plain-text nurture email for an independent auto shop owner who received 14 days of free AI social media content from Mbusiness Branding AI. Pain angle: One-time oil change customers who disappear. Jiffy Lube sends automated maintenance reminders at 3 months — they don't. That's why customers go back to the chains. Show what changes when AI sends reminders, requests reviews, and builds trust against national chain competitors. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Jiffy Lube sends reminders at 3 months. That's why they went back." Tone: direct, no fluff. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-06 (Day 7):**
```
Write a plain-text email for an auto shop owner announcing Week 2 content is ready. Transition into pitching Founders VIP (AI receptionist + reputation management + social posting, 75% off first month, 10 spots). Connect to their pain: losing repeat customers to chains with reminder systems and massive review counts. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Week 2 is here — and I want to make you an offer". Tone: confident, direct. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-07 (Day 9):**
```
Write a plain-text objection-handler for an auto shop owner. Handle: "I have regulars / my work speaks for itself." Reframe: their best customers are being reminded to go to Jiffy Lube every 3 months — without a system doing the same, word of mouth doesn't scale. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Your best customers are getting Jiffy Lube's texts. Not yours." Tone: direct, confident. Sign off: Mauriel, Mbusiness Branding AI. Length: 150–200 words.
```

**Email-08 (Day 11):**
```
Write a plain-text urgency email for an auto shop owner. Founders VIP spots are going. Connect urgency: every month without a retention system is another month chains pull those customers back. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "Founders VIP update — a few spots are gone". Tone: urgent but not pushy. Sign off: Mauriel, Mbusiness Branding AI. Length: 120–160 words.
```

**Email-09 (Day 14):**
```
Write a plain-text last-call email for an auto shop owner. Final Founders VIP email. Short, no pressure. CTA: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}. Subject line: "This is the last time I'll bring this up". Tone: zero pressure. Sign off: Mauriel, Mbusiness Branding AI. Length: 100–130 words.
```

---

## Email & SMS Copy Reference

> **How to use this section:**
> When building WF-03 in the GHL UI, each "Send Email" step has its own inline editor. Click the step → paste the **Subject** and **Body** from below. Same for SMS steps — paste the SMS text directly into the step. No templates needed.
>
> CTA link for all emails/SMS: `{{trigger_link.h1zRc64nntbXDFn4F8Wg}}`

---

### PLUMBING

#### Email-05-Plumbing (Day 3)
**Subject:** The plumber down the street just booked your next job

**Body:**
```
Hi {{contact.first_name}},

While you were on a job this week, someone called your business. You couldn't answer. They called the next plumber on their list — and booked with them.

This happens dozens of times a month for plumbers who don't have a system handling their phones. The shops that are growing right now aren't necessarily better plumbers. They just respond faster.

Customers call 2–3 companies and go with whoever answers first. If your phone goes to voicemail — you're invisible, even if you're the best plumber in the area.

An AI receptionist captures every call while you're on jobs. It answers basic questions, collects the lead's info, and books them on your calendar. You just show up.

If you want to stop losing jobs to whoever answers faster — let's talk:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Plumbing (Day 5)
```
{{contact.first_name}} — missing calls while on jobs? Those are booked jobs going to competitors. Fix it: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Plumbing (Day 7)
**Subject:** Week 2 content is ready — and I want to make you an offer

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 social media content is now in the Drive folder — 7 more posts ready to publish.

Now that you've seen what AI-generated social content looks like for your plumbing business, I want to ask you something: what if this was running automatically, every single week, without you thinking about it?

That's one piece of the Founders VIP Program. Along with it:
- 24/7 AI receptionist that captures every lead and books appointments while you're on jobs
- Automated review requests that build your Google reputation after every completed job
- AI-powered website designed for lead capture

This is what the plumbing businesses winning jobs 24/7 are running.

I'm offering Founders VIP at 75% off for the first 10 clients. Spots are almost full.

Book a quick strategy call and I'll walk you through exactly how it works:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Plumbing (Day 7)
```
{{contact.first_name}} — Founders VIP open: AI receptionist + social content + reviews, 75% off. Only 10 spots: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Plumbing (Day 9)
**Subject:** I am too busy right now — that is exactly what this solves

**Body:**
```
Hi {{contact.first_name}},

"I'm too busy right now."

I hear this from plumbing business owners constantly. And I get it — you're in the field, running the business, dealing with everything at once.

But here's the thing: being too busy is only a problem if new leads are still slipping through. If someone calls while you're under a sink and nobody answers — being busy just cost you a job.

The whole point of the AI receptionist is that it takes "busy" off your plate. You don't manage leads. You don't answer overflow calls. You don't chase follow-ups. You just show up to the appointments on your calendar.

If "too busy right now" is where you're at — this is exactly when you need this.

One call. 20 minutes. I'll show you how it works:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Plumbing (Day 11)
**Subject:** Founders VIP update — spots are going

**Body:**
```
Hi {{contact.first_name}},

Quick update on the Founders VIP Program.

When I opened founding spots at 75% off, I didn't know how quickly they'd fill. Most are gone.

I hold a limited number of spots per trade — I don't want to take on more clients than I can serve well, and I don't want 10 plumbers in the same market running the same content.

There are a small number of plumbing spots remaining.

Full package: AI-powered website, 24/7 AI receptionist, automated review requests, social media content — all at 75% off.

Book a call and I'll hold your spot:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Plumbing (Day 11)
```
{{contact.first_name}} — plumbing Founders VIP spots almost full. First to answer wins the job. Book before it closes: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Plumbing (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP offer closes when the 10 spots fill — I don't extend it, I don't make exceptions.

If you're a plumbing business owner who's been on the fence: the question isn't whether AI will change how service businesses operate — it already is. The question is whether you're in the group of plumbers who gets there first — at 75% off, with a setup built for your business — or whether you watch competitors pull ahead.

This is my last ask. Book the call if you want a spot. If not, I'll keep sending content and we'll stay in touch — no pressure.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Plumbing (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now or price goes back up: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### HVAC

#### Email-05-HVAC (Day 3)
**Subject:** HVAC peak season — are you capturing every call?

**Body:**
```
Hi {{contact.first_name}},

Summer is here. Your phone is ringing constantly. You're already booked out 2 weeks — and you're still missing calls.

Here's what's happening with every call you miss: that homeowner opens their phone, calls the next HVAC company on the list, and books whoever picks up. Peak season revenue is walking out the door because you can't be in the field and on the phone at the same time.

This is exactly what AI was built to solve. Every overflow call gets answered, captured, and routed — even at 10pm during a heat wave. Maintenance appointments get booked during your slower months while you focus on the rush jobs.

You're already doing the hard part. Let's make sure no call goes unanswered when it counts most:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-HVAC (Day 5)
```
{{contact.first_name}} — peak season = missed calls = revenue to competitors. Each one that goes to voicemail is a loss. Fix it: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-HVAC (Day 7)
**Subject:** Week 2 content is here — plus something important for HVAC season

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 content is in the Drive folder — 7 new posts ready to publish.

Now that you've seen what AI content looks like for HVAC, I want to talk about the bigger picture. Peak season is when you're most likely to miss calls, lose leads, and burn out — all at the same time.

The Founders VIP Program is built specifically for this:
- AI receptionist captures every overflow call during peak season — no more missed leads when you're slammed
- Automated maintenance reminders keep your slow season from going dry
- Social content keeps you visible year-round, not just when it's 95 degrees

I'm offering this at 75% off for the first 10 HVAC companies. HVAC spots are almost gone.

Book a quick call and let's go through it together:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-HVAC (Day 7)
```
{{contact.first_name}} — Founders VIP for HVAC: AI answering peak calls + maintenance reminders. 75% off, 10 spots: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-HVAC (Day 9)
**Subject:** I am slammed during peak season — that is exactly when you need this

**Body:**
```
Hi {{contact.first_name}},

"I'm slammed right now — I'll deal with this after the season."

This is the most common thing HVAC business owners tell me. And when it's 95 degrees and you're booked 3 weeks out, I understand why adding anything new sounds impossible.

But here's what's actually happening during that busy season: you're missing calls because you're on jobs. Those missed calls are calling the next HVAC company. The overflow is happening right now — not after the season.

Setting up the AI receptionist takes one 20-minute call. After that, every overflow call gets captured — even during your busiest peak weeks.

The setup is fast. The revenue recovery starts immediately.

Book a call — 20 minutes:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-HVAC (Day 11)
**Subject:** Founders VIP update — HVAC spots going fast

**Body:**
```
Hi {{contact.first_name}},

Quick Founders VIP update.

HVAC has been the most requested trade category — partly because peak season urgency is real. Business owners in HVAC see immediately why this matters.

The HVAC founding spots are nearly full. I limit spots per trade so I can deliver real results — not spread thin across too many companies.

Full offer: AI website, 24/7 AI receptionist for peak season overflow, automated maintenance reminders, social content, reputation management — at 75% off.

Book a call this week if you want in:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-HVAC (Day 11)
```
{{contact.first_name}} — HVAC Founders VIP almost full. Peak season is coming. Book your spot before it's gone: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-HVAC (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP offer closes when the 10 spots fill — I don't extend it.

If you're an HVAC business owner who's been thinking about it: peak season is either here or coming. The window to have the AI receptionist running before you're slammed is now, not after.

This is my last ask. Book the call if you're in. If not, no pressure — I'll keep sending content and we'll stay in touch.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-HVAC (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### ROOFING

#### Email-05-Roofing (Day 3)
**Subject:** The estimate you gave last week — did they go with you?

**Body:**
```
Hi {{contact.first_name}},

You gave a free estimate. Did the job. Sent the quote. Followed up once. Silence.

Meanwhile, the roofing company that followed up on day 2, day 5, and day 10 just got the contract.

Roofing estimates are high-ticket. Homeowners take time. They get 3 quotes, think about it, talk to their spouse, and decide somewhere in days 3–10. The company that wins isn't usually the cheapest or the best — it's the one that stayed in front of them during that window.

Automated follow-up sequences do this without you lifting a finger. Every estimate gets touched at the right intervals until the prospect decides. No more jobs going cold because you got busy.

Ready to close more of the estimates you're already giving?
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Roofing (Day 5)
```
{{contact.first_name}} — estimates going cold? Most roofers lose the job at day 3–10. Automated follow-up fixes that: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Roofing (Day 7)
**Subject:** Week 2 is ready — here is what closes more roofing estimates

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 content is in the Drive folder — ready to post.

Roofing is a high-ticket, high-trust business. Homeowners don't hire the first roofer they find — they hire the one who looks credible, has reviews, and follows up.

The Founders VIP Program handles all three:
- Consistent social content builds trust before they even call
- Automated estimate follow-up sequences keep your quotes from going cold
- Review requests after every job build your Google rating against fly-by-night competitors

This is what the roofing companies closing 60–70% of their estimates are running.

75% off for the first 10 roofers. A few spots left:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Roofing (Day 7)
```
{{contact.first_name}} — Founders VIP: automated estimate follow-up + reviews + social. 75% off. Limited spots: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Roofing (Day 9)
**Subject:** What the roofers who close more estimates do differently

**Body:**
```
Hi {{contact.first_name}},

There are roofers in your market closing 60–70% of their estimates. And roofers closing 20–30%.

The difference is almost never quality of work. It's follow-up.

The ones closing more have a system that touches every estimate at the right intervals:
- Day 1: estimate sent + personal follow-up
- Day 3: "Any questions about the estimate?"
- Day 7: social proof — a customer review or completed project photo
- Day 10: final outreach before they move on

Most homeowners decide between day 3 and day 10. If you're not in their inbox during that window — whoever is gets the job.

Automated estimate follow-up is a core piece of the Founders VIP. It runs without you touching it.

One call — I'll show you:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Roofing (Day 11)
**Subject:** Founders VIP update — roofing spots filling

**Body:**
```
Hi {{contact.first_name}},

Quick update. Roofing founding spots are almost full.

I want to be direct about why this offer exists: I'm building case studies. Founding clients get 75% off — I get proof the system delivers results for roofing businesses.

After the first 10 fill their spots, the price returns to standard rate.

A small number of roofing spots remain. Full package: AI website, 24/7 AI receptionist, automated estimate follow-up, reputation management, social content.

This is the week to move:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Roofing (Day 11)
```
{{contact.first_name}} — roofing Founders VIP spots almost gone. Estimate follow-up starts as soon as you're in: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Roofing (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP offer closes when the 10 spots fill. No extensions.

If you're a roofer who's been on the fence: the competitors closing more estimates aren't doing better work — they have better follow-up. At 75% off, the ROI on even one additional closed job is significant.

This is my last ask. Book the call if you want a spot. If not — no hard feelings.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Roofing (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### LANDSCAPING / LAWN

#### Email-05-Landscaping (Day 3)
**Subject:** Why your neighbor hired someone else for their lawn

**Body:**
```
Hi {{contact.first_name}},

Spring is here. Homeowners are searching "lawn care near me." And the company that shows up first — with the most reviews and the most social presence — gets the call.

If that's not you, you're invisible — even if you do better work than whoever they hired.

Seasonal businesses are tough because you have to re-win customers every single year. The landscaping companies that grow consistently are the ones that stayed visible all winter: social posts in January and February, re-booking texts in March, review requests after every fall cleanup.

By the time their competitors are fighting for spring clients, these businesses already have them locked in.

That system runs automatically. Let me show you what it looks like for your business:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Landscaping (Day 5)
```
{{contact.first_name}} — spring's almost here. Your clients are getting postcards from competitors. Are they locked in with you? {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Landscaping (Day 7)
**Subject:** Week 2 posts are in — plus how to lock in your spring clients now

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 posts are in the Drive folder.

Spring is either here or just around the corner — and the landscaping businesses that win this season locked in their clients before it hit.

The Founders VIP Program helps you do exactly that:
- Social content keeps you visible year-round — not just when the grass is growing
- AI follow-up re-books existing clients automatically before they go looking for someone new
- Review requests build the Google profile that wins calls from homeowners who don't know you yet

75% off for the first 10 landscaping businesses. A few founding spots left:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Landscaping (Day 7)
```
{{contact.first_name}} — Founders VIP: re-booking automation + social + reviews before spring. 75% off, limited spots: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Landscaping (Day 9)
**Subject:** The landscapers who are fully booked before spring even starts

**Body:**
```
Hi {{contact.first_name}},

The landscaping companies that open spring fully booked didn't get lucky. They stayed in front of their clients all winter.

- Social posts in January and February keeping their name visible
- Re-booking texts in March: "Spring is coming — want to lock in your weekly service?"
- Review requests after every fall cleanup building their Google profile

By the time their competitors are fighting for spring clients, these businesses already have them.

You can build this system before next spring — and none of it requires you to do it manually.

One call — let me walk you through the setup. 20 minutes:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Landscaping (Day 11)
**Subject:** Founders VIP update — a few spots left

**Body:**
```
Hi {{contact.first_name}},

Short update. The Founders VIP founding spots for landscaping are nearly full.

Spring timing makes this especially relevant. If you want the AI receptionist, social content, and re-booking system running before your peak season — setup needs to start now, not after spring is underway.

A few landscaping spots remain. Full package: AI website, AI receptionist, reputation management, social content — all at 75% off.

Book a call this week and I'll hold your spot:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Landscaping (Day 11)
```
{{contact.first_name}} — landscaping spots almost full. Spring is close. Book before they're gone: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Landscaping (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP founding offer closes when 10 spots fill. No extensions.

If you're a landscaping business owner who's been thinking about it: spring re-booking season is either here or weeks away. The window to have re-booking automation running before you need it is now.

This is my last ask. Book the call if you're in. If not — no pressure.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Landscaping (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### CLEANING

#### Email-05-Cleaning (Day 3)
**Subject:** Why price shoppers are not your real problem

**Body:**
```
Hi {{contact.first_name}},

Price shoppers are frustrating. But they're not why your revenue is stuck.

The real drain is clients who book once — and never come back. Not because they were unhappy. Because nobody reminded them it was time to rebook.

The cleaning businesses growing fastest right now have a simple system:
- Post-service review requests build their Google reputation so new clients find them
- Re-booking follow-ups turn one-time bookings into recurring clients
- Referral prompts turn happy clients into their best sales reps

None of this requires you to personally send anything. It all runs in the background.

You don't need more price shoppers — you need a system that keeps the good clients coming back:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Cleaning (Day 5)
```
{{contact.first_name}} — any first-time clients this month who haven't rebooked? That's revenue leaving. Let's fix the retention leak: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Cleaning (Day 7)
**Subject:** Week 2 is here — and a way to stop losing clients after the first booking

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 content is in the Drive folder — ready to publish.

The cleaning businesses growing fastest right now aren't necessarily getting more new clients. They're keeping the ones they already have.

The Founders VIP Program is built for retention:
- Automated re-booking follow-ups turn one-time bookings into recurring clients
- Review requests after every service build the Google reputation that brings new clients in
- Social content keeps your brand in front of people who might need you next month

75% off for the first 10 cleaning businesses. Spots are nearly full:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Cleaning (Day 7)
```
{{contact.first_name}} — Founders VIP: re-booking automation + review system. Retention made easy. 75% off: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Cleaning (Day 9)
**Subject:** The cleaning business owners who said the same thing you are thinking

**Body:**
```
Hi {{contact.first_name}},

Every cleaning business owner I work with says some version of this at first: "I just need more clients."

But when we dig in — the real problem isn't getting new clients. It's keeping them.

They have happy clients who never leave a review. First-time bookings who never come back. No system for re-booking. And they're spending money on ads trying to fill a leaky bucket instead of plugging it.

The cleaning businesses that grow don't have the most leads. They have the best retention. Re-booking follow-ups. Review requests. Referral prompts. These run automatically in the background while you focus on the work.

One call — I'll show you the full picture:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Cleaning (Day 11)
**Subject:** Founders VIP update — spots going

**Body:**
```
Hi {{contact.first_name}},

Quick update on Founders VIP spots.

Cleaning businesses have been one of the most active categories — owners in this space understand the retention and reputation problem clearly.

The cleaning business founding spots are almost full. These spots are limited because I limit my client count by design. When they're full, they're full.

Full package: AI website, AI receptionist, re-booking automation, reputation management, social content — 75% off for founders.

Book a call:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Cleaning (Day 11)
```
{{contact.first_name}} — cleaning Founders VIP spots almost gone. Re-booking automation + reviews. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Cleaning (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP offer closes when 10 spots fill. No exceptions.

If you're a cleaning business owner who's been on the fence: the businesses growing in your space aren't getting more clients — they're keeping the ones they have. At 75% off, the math works on retention alone.

This is my last ask. Book the call if you want in. If not — no pressure.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Cleaning (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### GENERAL CONTRACTING

#### Email-05-GeneralContracting (Day 3)
**Subject:** The referral that went to another contractor this week

**Body:**
```
Hi {{contact.first_name}},

You're deep in a project. Calls are going to voicemail. A homeowner saw your work and wanted to hire you — but couldn't reach you. They called a contractor who had before/after photos on Instagram, 40 Google reviews, and responded within an hour.

That contractor might do the same quality of work as you. But they look like the obvious choice — and they have a system to answer when they're on the job site.

This is the GC problem: every big project takes you off the market for months. By the time you surface, your pipeline is empty and you're starting over.

An AI receptionist captures every lead that comes in while you're working. Social content keeps your business visible even when you're heads-down. You surface from a project to a full pipeline instead of an empty one.

Let's talk about what this looks like for your business:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-GeneralContracting (Day 5)
```
{{contact.first_name}} — pipeline dry while you're on a job? Every missed call is a referral going elsewhere. Fix it: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-GeneralContracting (Day 7)
**Subject:** Week 2 posts plus how to stop the feast-or-famine cycle

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 content is in the Drive folder.

Now let's talk about the biggest problem in general contracting: the feast-or-famine cycle. You're slammed on a project, pipeline dries up, project ends, and you spend 2 months chasing the next job.

The Founders VIP Program breaks that cycle:
- AI receptionist captures leads and books discovery calls even when you're on a job site
- Social content keeps your work visible and attracts homeowners consistently
- Review requests build your reputation so referrals come to you without you asking

75% off for the first 10 contractors. A few founding spots remaining:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-GeneralContracting (Day 7)
```
{{contact.first_name}} — Founders VIP: AI receptionist + social content + reviews. Stop the famine. 75% off: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-GeneralContracting (Day 9)
**Subject:** What happens to your leads when you are on a job

**Body:**
```
Hi {{contact.first_name}},

Here's the GC cycle I see constantly: you land a big project, go heads-down for 3 months, and surface with zero pipeline.

The calls you missed while you were working? They went to competitors.

The homeowners who saw your work and wanted to hire you? They left a voicemail that got buried.

The feast-or-famine cycle doesn't have to be your reality. The AI receptionist captures every lead that comes in when you can't answer — books discovery calls, collects project details, schedules callbacks.

You come up for air to a full pipeline instead of starting from zero.

One call, 20 minutes:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-GeneralContracting (Day 11)
**Subject:** Founders VIP update — general contracting spots

**Body:**
```
Hi {{contact.first_name}},

Quick update. General contracting founding spots are nearly full.

General contracting has the highest average job value of any trade I work with — and also the highest cost of a missed lead. If the AI receptionist captures just one project you would have otherwise lost, it pays for itself for the year.

At 75% off founding pricing, the math is simple.

A few GC spots remain. Book a call this week:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-GeneralContracting (Day 11)
```
{{contact.first_name}} — GC Founders VIP spots almost full. Never surface from a big job to an empty pipeline again: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-GeneralContracting (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

Founders VIP closes when 10 spots fill. No extensions.

If you're a contractor who's been thinking about it: the feast-or-famine cycle costs more than the program. One captured lead during a busy project more than covers the founding rate for the year.

This is my last ask. Book the call if you want in. If not — I'll keep sending content.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-GeneralContracting (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### BARBER / HAIR STYLIST

#### Email-05-Barber (Day 3)
**Subject:** The chair that was empty because no one sent a reminder

**Body:**
```
Hi {{contact.first_name}},

A no-show hits you immediately — empty chair, lost revenue. But the slower damage is the client who "went somewhere else." Not because they didn't like you. Because they got busy, drifted a few weeks, and the shop down the street texted a rebooking link before you did.

The barbers and stylists who keep full books aren't just talented — they have a system:
- Appointment reminders 24 hours and 2 hours out that cut no-shows significantly
- "Ready to book again?" texts at the 3-week mark so clients come back before they wander
- Birthday and loyalty touchpoints that make clients feel remembered

These run automatically. You focus on the cut and the conversation. The system handles the business side.

One call — let me show you what this looks like:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Barber (Day 5)
```
{{contact.first_name}} — how many clients haven't rebooked in 3+ weeks? They're drifting. A text brings them back. Let's set it up: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Barber (Day 7)
**Subject:** Week 2 is in your inbox — plus how to fill every open slot in your book

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 content is in the Drive folder.

Quick question: what does an empty chair cost you? Not just today — but every week, compounded across a year?

The Founders VIP Program is built to keep your book full:
- Appointment reminders cut no-shows — clients get reminded 24 hours and 2 hours out
- Re-booking texts go out at the 3-week mark so clients don't drift to another shop
- Review requests build your Google and Yelp profile for new clients who are searching
- Social content shows your work so new clients know your style before they call

75% off for the first 10 shops:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Barber (Day 7)
```
{{contact.first_name}} — Founders VIP: reminders + re-booking + reviews. Keep your book full. 75% off: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Barber (Day 9)
**Subject:** Fully booked now — what about 3 months from now?

**Body:**
```
Hi {{contact.first_name}},

Business feels good right now. Book is full, clients are coming in.

The question isn't today — it's what happens in 3 months when natural churn hits. The client who went on vacation and hasn't rebooked. The one who tried a new place. The one who just got busy and let it slide.

Every shop has a steady drip of clients drifting out the door. The shops that stay consistently full are the ones automatically following up — "it's been 3 weeks, ready to book?" — so clients come back before they wander.

The system runs in the background. Your work stays exactly the same.

One call — let me show you:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Barber (Day 11)
**Subject:** Founders VIP update — shop spots filling

**Body:**
```
Hi {{contact.first_name}},

Quick Founders VIP update. Barber and salon spots are almost full.

I want to address something I hear from shop owners: "I don't know if AI fits my business — it's personal service."

That's exactly why it works. AI handles the impersonal side — reminders, follow-ups, review requests, re-booking texts — so you can focus entirely on the personal side: the cut, the conversation, the relationship.

You stay you. The system handles the business side.

75% off for founders. A few shop spots remain:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Barber (Day 11)
```
{{contact.first_name}} — shop spots almost gone. Keep your book full with reminders + re-booking automation. Book the call: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Barber (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

The Founders VIP offer closes when 10 spots fill. I don't extend it.

If you're a barber or stylist who's been on the fence: one empty chair per week, 52 weeks — that's real money. The re-booking automation alone can recover that. At 75% off founding pricing, it takes almost nothing to get started.

This is my last ask. Book the call if you're in. If not — no hard feelings.

Keep posting that content. It works.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Barber (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

### AUTOMOTIVE

#### Email-05-Automotive (Day 3)
**Subject:** Why your customers keep going back to the chain

**Body:**
```
Hi {{contact.first_name}},

You do better work. You know your customers by name. They still go to the chain for their next oil change.

Why? Because Jiffy Lube sends a text at 3 months reminding them it's due. And you don't.

You're not losing to them on quality. You're losing to them on follow-up — and they've spent $20 million building a CRM to do it.

You don't need a $20 million CRM. You need:
- AI maintenance reminders at 3, 6, and 12 months based on the service performed
- Review requests after service that build the Google rating new customers check
- Social content that shows your expertise and keeps regulars remembering you exist

Independent shops that implement this are seeing real retention recovery.

One call — let me show you how it works:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-04-Automotive (Day 5)
```
{{contact.first_name}} — customers not coming back at 3 months? Jiffy Lube sends a reminder. You don't. Match them: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-06-Automotive (Day 7)
**Subject:** Week 2 posts ready — and how to stop losing repeat customers to chains

**Body:**
```
Hi {{contact.first_name}},

Your Week 2 posts are in the Drive folder.

The chains spend millions on retention technology. The Founders VIP Program lets you run the same playbook for a fraction of the cost:
- AI maintenance reminders at 3 months, 6 months, and 1 year — based on the service performed
- Review requests after every visit that build the Google rating new customers check before choosing
- Social content showing your expertise builds trust before they ever walk in

Independent shops that run this system see meaningful retention recovery against the chains.

75% off for the first 10 auto shops:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-05-Automotive (Day 7)
```
{{contact.first_name}} — Founders VIP: maintenance reminders + reviews + social. Beat the chains. 75% off: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-07-Automotive (Day 9)
**Subject:** Independent shop owners who said business was fine — then the chains ran a promo

**Body:**
```
Hi {{contact.first_name}},

"Business is fine — I have regulars."

Then the chain runs a $19.99 oil change promotion. Your regulars think "I'll just go this once." And because your shop didn't send a reminder that their service was due — they didn't even think of you.

You're not losing on quality. You're losing on follow-up. The chain has a $20M CRM doing it automatically.

You don't need a $20M CRM. You need AI maintenance reminders at 3, 6, and 12 months. Review requests that build your Google rating. Social content that reminds regulars you exist.

One call — I'll show you how independent shops compete:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### Email-08-Automotive (Day 11)
**Subject:** Founders VIP update — auto shop spots

**Body:**
```
Hi {{contact.first_name}},

Quick update. Auto shop founding spots are almost full.

One number: the average independent auto shop loses 30–40% of first-time customers before a second visit — simply because there was no follow-up.

AI maintenance reminders at 3 months recover a meaningful portion of those customers. Review requests build the Google rating that makes new customers choose the independent shop over the chain.

At 75% off founding pricing — one recovered customer per month more than covers the cost.

A few auto shop spots remain:
{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-06-Automotive (Day 11)
```
{{contact.first_name}} — auto shop Founders VIP spots almost full. Maintenance reminders + reviews. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

#### Email-09-Automotive (Day 14)
**Subject:** This is the last time I will bring this up

**Body:**
```
Hi {{contact.first_name}},

Last email about this.

Founders VIP closes when 10 spots fill. No extensions.

If you're an auto shop owner who's been thinking about it: Jiffy Lube spends millions keeping your regulars coming back to them. At 75% off founding pricing, you can run the same retention playbook for a fraction of what they spend.

This is my last ask. Book the call if you want in. If not — no pressure.

Keep using the social content. It'll keep working for you.

{{trigger_link.h1zRc64nntbXDFn4F8Wg}}

— Mauriel
Mbusiness Branding AI
```

#### SMS-07-Automotive (Day 14)
```
{{contact.first_name}} — last call. Founders VIP closes when spots fill, no extensions. Book now: {{trigger_link.h1zRc64nntbXDFn4F8Wg}}
```

---

*Updated: March 11, 2026 | Mbusiness Branding AI*
