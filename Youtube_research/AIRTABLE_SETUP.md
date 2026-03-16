# Airtable Setup — YouTube Outlier Research Base

Step-by-step guide to create the Airtable base that WF5 writes data into.

---

## Step 1 — Create a New Base

1. Go to airtable.com → Log in
2. Click **+ Add a base** → **Start from scratch**
3. Name it: `YouTube Outlier Research`
4. Click **Create**

---

## Step 2 — Create Table 1: Channels

This is the table YOU manage — add channels you want to monitor.

Rename the default table to **Channels**.

### Fields to create:

| Field Name | Field Type | Notes |
|------------|------------|-------|
| Channel Name | Single line text | Default "Name" field — rename it |
| Channel ID | Single line text | The UCxxxxxx ID from YouTube |
| Active | Checkbox | Check = workflow scans this channel |
| Last Scanned | Date | Workflow updates this automatically |

### How to find a YouTube Channel ID:
1. Go to any YouTube channel
2. Look at the URL: `youtube.com/channel/UCxxxxxxxxxx` — copy the `UCxxxxxxxxxx` part
3. If the URL shows `@username` instead, click **About** → scroll to share icon → copy channel ID

### Add your first 3 channels to test with:
| Channel Name | Channel ID | Active |
|--------------|------------|--------|
| (your niche channel) | UCxxxxx | ✓ |
| (your niche channel) | UCxxxxx | ✓ |
| (your niche channel) | UCxxxxx | ✓ |

**Suggested starting channels for AI/home services niche:**
- Search YouTube for: "AI for small business", "home services marketing", "local business growth"
- Pick channels with 10K–200K subscribers that post regularly

---

## Step 3 — Create Table 2: Outlier Videos

Click **+ Add a table** → Name it: **Outlier Videos**

### Fields to create:

| Field Name | Field Type | Options/Notes |
|------------|------------|---------------|
| Video Title | Single line text | Default "Name" field — rename it |
| Video ID | Single line text | YouTube video ID (e.g. `dQw4w9WgXcQ`) |
| Video URL | Formula | Formula: `"https://youtube.com/watch?v="&{Video ID}` |
| Channel | Link to another record | Link to → **Channels** table |
| Views | Number | Format: Integer, no decimals |
| Channel Avg Views | Number | Format: Integer, no decimals |
| Outlier Score | Formula | Formula: `ROUND({Views}/{Channel Avg Views},1)&"x"` |
| Thumbnail | URL | Stores YouTube thumbnail link |
| Transcript | Long text | Enable "Rich text formatting" = OFF |
| Rewritten Script | Long text | Enable "Rich text formatting" = OFF |
| Status | Single select | Add options: New, Script Ready, Published |
| Date Discovered | Date | Format: ISO (YYYY-MM-DD) |

### How to add each field:
1. Click the **+** at the end of the field header row
2. Choose the field type
3. Name it exactly as shown above (spelling matters — the workflow uses these exact names)

---

## Step 4 — Get Your Airtable Token and Base ID

### Personal Access Token:
1. Go to: airtable.com/create/tokens
2. Click **Create new token**
3. Name it: `n8n-youtube-research`
4. Scopes — add these:
   - `data.records:read`
   - `data.records:write`
5. Access — click **+ Add a base** → select `YouTube Outlier Research`
6. Click **Create token**
7. **Copy the token immediately** — it only shows once
8. Paste it in the n8n workflow Config node as `AIRTABLE_TOKEN`

### Base ID:
1. Open your `YouTube Outlier Research` base in Airtable
2. Look at the URL: `airtable.com/appXXXXXXXXXXXXXX/...`
3. Copy the `appXXXXXXXXXXXXXX` part — that is your Base ID
4. Paste it in the n8n workflow Config node as `AIRTABLE_BASE_ID`

---

## Step 5 — Create a View for Your Content Queue

In the **Outlier Videos** table:
1. Click **+ Add view** → **Gallery**
2. Name it: `Content Queue`
3. Set the cover image to: `Thumbnail`
4. Group by: `Status`

Now you can see your content queue visually, move cards from "New" → "Script Ready" → "Published" as you use each script.

---

## Verification Checklist

- [ ] Base created: `YouTube Outlier Research`
- [ ] Table 1 `Channels` — all 4 fields created with correct names
- [ ] Table 2 `Outlier Videos` — all 12 fields created with correct names
- [ ] At least 2 channels added with Channel ID and Active = checked
- [ ] Airtable token copied into n8n Config node
- [ ] Base ID copied into n8n Config node
- [ ] Gallery view "Content Queue" created
