# YouTube Outlier Research → Brand Voice Content Workflow

A repeatable SOP for finding proven video topics and turning them into original Mbusiness Branding AI scripts.

---

## The Strategy

Find videos that dramatically outperform their channel's average views — these are **outliers**. They prove a topic resonates. Extract the transcript, rewrite it in your brand voice. You get proven topics, your original take.

---

## Step 1 — Find Niche Channels

Search YouTube for keywords your audience cares about:
- "AI for small business"
- "home services marketing"
- "local business automation"
- "AI receptionist for contractors"
- "Google reviews for small business"
- "lead generation for home services"

**Target:** Channels with 5K–500K subscribers. Big enough to have proven data, small enough the algorithm hasn't saturated them.

---

## Step 2 — Identify Outlier Videos

**An outlier = any video with 3x or more the channel's average views.**

**Manual method:**
1. Go to the channel → Sort by "Most Popular"
2. Check recent uploads (last 10–20 videos) — note the average view count
3. Flag any video at 3x+ that average

**Best free tool: Spotter Studio**
- Go to spotter.studio
- Enter any channel URL
- It shows an "outlier score" for every video — immediately highlights what crushed vs. what flopped

**Other tools:**
- VidIQ (free tier) — outlier detection built into channel analysis
- TubeBuddy — similar view ratio tracking

**Save:** Title + URL of 5–10 outlier videos per channel.

---

## Step 3 — Extract the Transcript

**Option A — YouTube built-in (free, 30 seconds):**
1. Open the video on YouTube
2. Click `...` (More) below the video → **Show transcript**
3. Click the three dots in the transcript panel → **Toggle timestamps** (off)
4. Select all text → Copy

**Option B — Tactiq Chrome extension (easier):**
- Install Tactiq from Chrome Web Store
- Open any YouTube video → click the Tactiq icon → Copy transcript
- Can export directly to Google Docs

**Option C — n8n automation (coming):**
- See `wf5-youtube-transcript-to-script.json` (to be built)
- Drop a URL into a webhook → get back a full transcript + rewritten script

---

## Step 4 — Rewrite in Brand Voice

Paste this into Claude along with the transcript:

```
You are writing a YouTube video script for Mbusiness Branding AI.

Brand voice rules:
- Tone: Confident, honest, direct, no fluff, straight to the point
- Signature word: Use "Perfect!" naturally — it shows solution-oriented energy
- Never say "I can't" — always keep it action-focused and positive
- Audience: Small home services business owners (plumbers, electricians, roofers, HVAC, landscapers) who are too busy serving customers to figure out AI tools on their own

Task: Rewrite the transcript below as a new original video script in my brand voice. Keep the proven hook structure and core topic. Change all examples to fit the home services world. Where relevant, weave in what Mbusiness Branding AI does: AI-powered websites for lead capture, 24/7 AI receptionist, Google review management, social media creation.

[PASTE TRANSCRIPT HERE]
```

**Output:** Full script, proven topic, your voice — ready to record.

---

## Recommended Tools Summary

| Step | Free | Paid Upgrade |
|------|------|--------------|
| Find channels | YouTube search | VidIQ, TubeBuddy |
| Identify outliers | Spotter Studio | VidIQ Boost (~$10/mo) |
| Extract transcript | YouTube built-in | Tactiq Chrome extension |
| Rewrite in brand voice | Claude (chat) | n8n automation (WF5) |

---

## Tracking — Outliers Found

| Date | Channel | Video Title | Views | Channel Avg | Outlier Score | Script Created? |
|------|---------|-------------|-------|-------------|---------------|-----------------|
| (add entries here) | | | | | | |

---

## Notes

- Run this weekly — 1 outlier → 1 script → 1 video
- Stack 4 scripts before filming so you're always ahead
- The best outlier titles often become your best thumbnail ideas too
- Cross-reference every script with the Founders VIP Program — find a way to tie the content back to the offer

---

## Automated Version (n8n WF5)

The entire workflow above (Steps 2–4) is automated via n8n. Every morning at 8am:
1. n8n reads your channel list from Airtable
2. Pulls the last 20 videos from each channel via YouTube API
3. Calculates average views, flags any video at 3x or more
4. Extracts the transcript (Supadata API)
5. Rewrites it in Mbusiness brand voice (Claude API)
6. Saves everything to Airtable — transcript + script, ready to use

**Setup files:**
- [API_KEYS_SETUP.md](API_KEYS_SETUP.md) — how to get all 4 API keys (takes ~20 min)
- [AIRTABLE_SETUP.md](AIRTABLE_SETUP.md) — how to build the Airtable base
- [wf5-youtube-outlier-research.json](../video-pipeline/n8n-workflows/wf5-youtube-outlier-research.json) — import into n8n

**You only manage:** The Airtable Channels table (add channels, check Active box). Everything else is hands-free.
