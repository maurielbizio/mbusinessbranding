# API Keys Setup — WF5 YouTube Outlier Research

You need 4 API keys. Get them in this order — takes about 20 minutes total.

---

## Key 1 — YouTube Data API v3
**Cost:** Free (10,000 requests/day — more than enough)
**Used for:** Getting video lists and view counts from YouTube channels

### Steps:
1. Go to: console.cloud.google.com
2. Sign in with your Google account
3. Click **Select a project** (top left) → **New Project**
   - Name: `mbusiness-youtube-research`
   - Click **Create**
4. Make sure the new project is selected in the dropdown
5. Click the search bar at the top → search: `YouTube Data API v3`
6. Click the result → click **Enable**
7. In the left sidebar click **Credentials**
8. Click **+ Create Credentials** → **API key**
9. Copy the API key that appears
10. Click **Edit API key** → under "API restrictions" select "Restrict key" → choose `YouTube Data API v3` → Save

**Paste into n8n Config node:** `YOUTUBE_API_KEY`

---

## Key 2 — Supadata API
**Cost:** Free (25 transcript extractions/month) — upgrade to $9/mo for 250/month if needed
**Used for:** Extracting the full text transcript from YouTube videos

### Steps:
1. Go to: supadata.ai
2. Click **Get Started** or **Sign Up**
3. Create an account with email
4. Once logged in, go to your **Dashboard** or **API Keys** section
5. Copy your API key

**Paste into n8n Config node:** `SUPADATA_API_KEY`

**Note on free tier:** 25 transcripts/month. If you run WF5 daily and typically find 1–2 outliers per run, you'll stay within the free tier. Paid plan is $9/month for 250.

---

## Key 3 — Anthropic API (Claude)
**Cost:** ~$0.01 per script rewrite. $5 credit lasts 500 rewrites.
**Used for:** Rewriting video transcripts in your brand voice automatically

### What this is:
Anthropic is the company that makes Claude — the same AI you're talking to right now. The API lets n8n talk to Claude programmatically, so the workflow can automatically rewrite each transcript without you doing anything manually.

### Steps:
1. Go to: console.anthropic.com
2. Click **Sign Up** — create an account
3. You will be asked to add a credit card
4. Click **Billing** in the left sidebar
5. Click **Add credit** → add **$5** to start (this covers ~500 script rewrites)
6. Click **API Keys** in the left sidebar
7. Click **Create Key**
   - Name: `n8n-youtube-research`
8. Copy the key (starts with `sk-ant-...`)

**Paste into n8n Config node:** `ANTHROPIC_API_KEY`

**Note:** The workflow uses `claude-haiku-4-5-20251001` — the fastest and cheapest Claude model. Perfect for script rewrites.

---

## Key 4 — Airtable Personal Token
**Cost:** Free
**Used for:** Saving all data (titles, views, transcripts, scripts) into your Airtable base

### Steps:
See [AIRTABLE_SETUP.md](AIRTABLE_SETUP.md) Step 4 — it walks through getting the token and Base ID together.

**Paste into n8n Config node:**
- Token → `AIRTABLE_TOKEN`
- Base ID → `AIRTABLE_BASE_ID`

---

## Paste Keys Into n8n

1. Open n8n: https://n8n.mbusinessbrandingai.com
2. Import the workflow: **Workflows** → **Import from file** → select `wf5-youtube-outlier-research.json`
3. Open the workflow → click the **Config** node
4. Replace each `PASTE_YOUR_xxx_HERE` value with your real keys:
   - `YOUTUBE_API_KEY` → from Key 1
   - `SUPADATA_API_KEY` → from Key 2
   - `ANTHROPIC_API_KEY` → from Key 3
   - `AIRTABLE_TOKEN` → from Key 4
   - `AIRTABLE_BASE_ID` → from Key 4 (base ID)
5. Click **Save** on the Config node
6. Click **Save** on the workflow (top right)

---

## Test Run

1. Make sure you have at least 2 channels in your Airtable Channels table with Active checked
2. In n8n, click **Test workflow** (the play button)
3. Watch the execution — click the **Research All Channels** node to see live output
4. After it finishes, open Airtable → Outlier Videos table
5. You should see new records with transcripts and rewritten scripts

---

## Activate the Daily Schedule

Once the test works:
1. Click the toggle at the top of the n8n workflow to **Activate** it
2. The workflow will now run automatically every day at 8am
3. You wake up to fresh scripts in Airtable — ready to record

---

## Troubleshooting

| Error | Likely Cause | Fix |
|-------|-------------|-----|
| `quotaExceeded` | YouTube API daily limit hit | Wait until midnight Pacific — resets daily |
| `401 Unauthorized` | Wrong API key | Double-check the key in Config node |
| `records not found` | Wrong Airtable Base ID | Re-copy the `appXXXX` ID from the URL |
| `Transcript unavailable` | Video has no captions | Normal — Claude will write from title instead |
| `No active channels` | Channels table is empty or Active unchecked | Add channels and check the Active box |
