<?php
/**
 * Template Name: Video Studio
 * Description: AI Video Creation Pipeline
 */
get_header();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Video Studio — Mbusiness Branding AI</title>
<style>
/* ─── Reset & Base ─────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: #0f0f0f !important;
  color: #e5e7eb !important;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
  min-height: 100vh;
}

/* Hide WordPress chrome */
.site-header,
.site-footer,
.entry-title,
.page-header,
.breadcrumb-trail,
#masthead,
#colophon,
.ast-breadcrumbs-wrapper,
.ast-above-header-wrap,
.ast-below-header-section,
.footer-widget-area,
.site-below-footer-wrap {
  display: none !important;
}

#wpadminbar { display: none !important; }
html { margin-top: 0 !important; }

.entry-content,
.ast-article-single,
.single-layout-container,
#primary,
#content,
.content-area,
.site-content,
.ast-container,
.container,
.ast-page-builder-template .entry-content {
  padding: 0 !important;
  margin: 0 !important;
  max-width: 100% !important;
  width: 100% !important;
}

/* ─── App Shell ─────────────────────────────────────────────────────────────── */
#vs-app {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 20px 60px;
}

/* ─── Header ────────────────────────────────────────────────────────────────── */
.vs-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 24px 0 32px;
  border-bottom: 1px solid #2a2a2a;
  margin-bottom: 32px;
}
.vs-header-icon { font-size: 32px; line-height: 1; }
.vs-header h1 {
  font-size: 26px;
  font-weight: 700;
  color: #e5e7eb;
  letter-spacing: -0.3px;
}
.vs-header p {
  font-size: 13px;
  color: #9ca3af;
  margin-top: 2px;
}

/* ─── Step Cards ─────────────────────────────────────────────────────────────── */
.vs-step {
  background: #1a1a1a;
  border: 1px solid #2a2a2a;
  border-radius: 12px;
  padding: 28px;
  margin-bottom: 24px;
}
.vs-step.vs-hidden { display: none; }

.vs-step-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: #9ca3af;
  margin-bottom: 6px;
}
.vs-step-title {
  font-size: 20px;
  font-weight: 700;
  color: #e5e7eb;
  margin-bottom: 20px;
}

/* ─── Tabs ───────────────────────────────────────────────────────────────────── */
.vs-tabs {
  display: flex;
  gap: 4px;
  background: #0f0f0f;
  border: 1px solid #2a2a2a;
  border-radius: 8px;
  padding: 4px;
  width: fit-content;
  margin-bottom: 20px;
}
.vs-tab {
  padding: 8px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  background: transparent;
  color: #9ca3af;
  transition: all 0.15s ease;
}
.vs-tab.active {
  background: #3b82f6;
  color: #fff;
}
.vs-tab:hover:not(.active) { color: #e5e7eb; background: #2a2a2a; }

/* ─── Form Controls ─────────────────────────────────────────────────────────── */
textarea, input[type="text"] {
  width: 100%;
  background: #0f0f0f;
  border: 1px solid #2a2a2a;
  border-radius: 8px;
  color: #e5e7eb;
  font-size: 14px;
  font-family: inherit;
  padding: 12px 14px;
  outline: none;
  transition: border-color 0.15s;
  resize: vertical;
}
textarea:focus, input[type="text"]:focus { border-color: #3b82f6; }
textarea { min-height: 160px; line-height: 1.6; }

.vs-field-label {
  font-size: 13px;
  color: #9ca3af;
  margin-bottom: 8px;
  display: block;
}

/* ─── Buttons ────────────────────────────────────────────────────────────────── */
.vs-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  text-decoration: none;
  line-height: 1.4;
}
.vs-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.vs-btn-primary { background: #3b82f6; color: #fff; }
.vs-btn-primary:hover:not(:disabled) { background: #2563eb; }
.vs-btn-success { background: #22c55e; color: #fff; }
.vs-btn-success:hover:not(:disabled) { background: #16a34a; }
.vs-btn-danger { background: #ef4444; color: #fff; }
.vs-btn-danger:hover:not(:disabled) { background: #dc2626; }
.vs-btn-outline {
  background: transparent;
  color: #9ca3af;
  border: 1px solid #2a2a2a;
}
.vs-btn-outline:hover:not(:disabled) { border-color: #9ca3af; color: #e5e7eb; }
.vs-btn-ghost {
  background: transparent;
  color: #3b82f6;
  border: 1px solid #3b82f6;
}
.vs-btn-ghost:hover:not(:disabled) { background: rgba(59,130,246,0.1); }
.vs-btn-sm { padding: 6px 14px; font-size: 13px; }

/* ─── File Upload ────────────────────────────────────────────────────────────── */
.vs-file-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 8px;
}
.vs-file-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: #0f0f0f;
  border: 1px dashed #3b82f6;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #3b82f6;
  font-weight: 500;
  transition: background 0.15s;
}
.vs-file-label:hover { background: rgba(59,130,246,0.07); }
.vs-file-label input { display: none; }
.vs-filename {
  font-size: 13px;
  color: #22c55e;
  display: flex;
  align-items: center;
  gap: 6px;
}
.vs-filename.empty { color: #9ca3af; }

.vs-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 8px;
}

/* ─── Status / Error ─────────────────────────────────────────────────────────── */
.vs-status {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(59,130,246,0.08);
  border: 1px solid rgba(59,130,246,0.25);
  border-radius: 8px;
  font-size: 14px;
  color: #93c5fd;
  margin-top: 16px;
}
.vs-status.vs-hidden { display: none; }
.vs-error {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: 8px;
  font-size: 14px;
  color: #fca5a5;
  margin-top: 16px;
}
.vs-error.vs-hidden { display: none; }

/* ─── Spinner ────────────────────────────────────────────────────────────────── */
.vs-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(147,197,253,0.3);
  border-top-color: #93c5fd;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Step 1 actions ─────────────────────────────────────────────────────────── */
.vs-step1-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
  flex-wrap: wrap;
}
.vs-count-badge {
  font-size: 13px;
  color: #9ca3af;
}

/* ─── Image Gallery ──────────────────────────────────────────────────────────── */
.vs-gallery-meta {
  font-size: 14px;
  color: #9ca3af;
  margin-bottom: 20px;
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
.vs-gallery-meta span { display: flex; align-items: center; gap: 5px; }
.dot-total { color: #e5e7eb; }
.dot-approved { color: #22c55e; }
.dot-rejected { color: #ef4444; }
.dot-pending { color: #f59e0b; }

.vs-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.vs-img-card {
  background: #0f0f0f;
  border: 2px solid #2a2a2a;
  border-radius: 10px;
  overflow: hidden;
  transition: border-color 0.15s;
}
.vs-img-card.state-approved { border-color: #22c55e; }
.vs-img-card.state-rejected { border-color: #ef4444; opacity: 0.55; }

.vs-img-wrap {
  width: 100%;
  aspect-ratio: 1;
  background: #111;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}
.vs-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.vs-img-placeholder {
  font-size: 32px;
  color: #2a2a2a;
}
.vs-img-status-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.badge-approved { background: #166534; color: #bbf7d0; }
.badge-rejected { background: #7f1d1d; color: #fecaca; }
.badge-pending  { background: #1c1917; color: #d6d3d1; }
.badge-loading  { background: #1e3a5f; color: #bfdbfe; }

.vs-img-body {
  padding: 10px;
}
.vs-img-desc {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.4;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.vs-img-actions {
  display: flex;
  gap: 6px;
}
.vs-img-actions button {
  flex: 1;
  padding: 6px 4px;
  border: 1px solid #2a2a2a;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
  background: #1a1a1a;
  transition: all 0.15s;
  line-height: 1;
}
.vs-img-actions button:hover { transform: scale(1.08); }
.vs-img-actions .btn-approve:hover, .vs-img-card.state-approved .btn-approve {
  background: rgba(34,197,94,0.15);
  border-color: #22c55e;
}
.vs-img-actions .btn-reject:hover, .vs-img-card.state-rejected .btn-reject {
  background: rgba(239,68,68,0.15);
  border-color: #ef4444;
}

.vs-step2-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

/* ─── Step 3 — Customize ─────────────────────────────────────────────────────── */
.vs-customize-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}
@media (max-width: 600px) { .vs-customize-grid { grid-template-columns: 1fr; } }

.vs-field { display: flex; flex-direction: column; }
.vs-field.full { grid-column: 1 / -1; }

.vs-video-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.vs-video-card {
  background: #0f0f0f;
  border: 1px solid #2a2a2a;
  border-radius: 10px;
  overflow: hidden;
}
.vs-video-card video {
  width: 100%;
  aspect-ratio: 9/16;
  object-fit: cover;
  background: #000;
  display: block;
  max-height: 280px;
}
.vs-video-card-body { padding: 10px; }
.vs-video-card-desc {
  font-size: 12px;
  color: #9ca3af;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.vs-section-subtitle {
  font-size: 13px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 14px;
  padding-bottom: 8px;
  border-bottom: 1px solid #2a2a2a;
}
.vs-music-section { margin-bottom: 24px; }

/* ─── Step 4 — Downloads ─────────────────────────────────────────────────────── */
.vs-download-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
.vs-download-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: #0f0f0f;
  border: 1px solid #2a2a2a;
  border-radius: 10px;
  padding: 14px 18px;
  flex-wrap: wrap;
}
.vs-download-item-info {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}
.vs-download-icon { font-size: 22px; flex-shrink: 0; }
.vs-download-title {
  font-size: 15px;
  font-weight: 600;
  color: #e5e7eb;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 400px;
}
.vs-download-desc {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 400px;
}
.vs-download-actions { display: flex; gap: 8px; flex-shrink: 0; }

.vs-success-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  background: rgba(34,197,94,0.08);
  border: 1px solid rgba(34,197,94,0.3);
  border-radius: 8px;
  font-size: 14px;
  color: #86efac;
  margin-bottom: 20px;
}

/* ─── Divider ─────────────────────────────────────────────────────────────────── */
.vs-divider { height: 1px; background: #2a2a2a; margin: 20px 0; }

/* ─── Progress bar ───────────────────────────────────────────────────────────── */
.vs-progress-wrap { margin-top: 12px; }
.vs-progress-label { font-size: 13px; color: #9ca3af; margin-bottom: 6px; }
.vs-progress-bar {
  height: 6px;
  background: #2a2a2a;
  border-radius: 3px;
  overflow: hidden;
}
.vs-progress-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 3px;
  transition: width 0.3s ease;
  width: 0%;
}
</style>
</head>
<body>
<div id="vs-app">

  <!-- ── Header ──────────────────────────────────────────────────────────── -->
  <div class="vs-header">
    <div class="vs-header-icon">🎬</div>
    <div>
      <h1>Video Studio</h1>
      <p>AI-powered image & video creation pipeline</p>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!-- STEP 1 — INPUT                                                        -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div class="vs-step" id="step1">
    <div class="vs-step-label">Step 1 of 4</div>
    <div class="vs-step-title">Describe Your Images</div>

    <!-- Tabs -->
    <div class="vs-tabs">
      <button class="vs-tab active" id="tab-type" onclick="switchTab('type')">📝 Type Descriptions</button>
      <button class="vs-tab" id="tab-csv"  onclick="switchTab('csv')">📄 Upload CSV</button>
    </div>

    <!-- Type Tab -->
    <div id="panel-type">
      <label class="vs-field-label" for="descriptions-textarea">Enter one image description per line:</label>
      <textarea
        id="descriptions-textarea"
        placeholder="A sunset over snow-capped mountains&#10;A professional HVAC technician working on an AC unit&#10;A modern kitchen renovation with marble countertops&#10;A friendly plumber fixing a sink"
        oninput="updateDescCount()"
      ></textarea>
      <p class="vs-hint">Each line becomes one image prompt. Be specific for best results.</p>
    </div>

    <!-- CSV Tab -->
    <div id="panel-csv" style="display:none">
      <label class="vs-field-label">Upload a CSV file — one description per row, first column used:</label>
      <div class="vs-file-row">
        <label class="vs-file-label">
          <input type="file" id="csv-file-input" accept=".csv,text/csv" onchange="handleCsvUpload(this)">
          📎 Choose CSV File
        </label>
        <span class="vs-filename empty" id="csv-filename">No file selected</span>
      </div>
      <p class="vs-hint">Format: description in column A. Header row is optional — if first row contains "description" or "prompt" it will be skipped.</p>
      <div id="csv-preview" style="margin-top:14px; display:none">
        <div class="vs-section-subtitle">Preview (<span id="csv-count">0</span> descriptions loaded)</div>
        <ul id="csv-preview-list" style="list-style:none; padding:0; max-height:120px; overflow-y:auto;"></ul>
      </div>
    </div>

    <!-- Actions -->
    <div class="vs-step1-actions">
      <button class="vs-btn vs-btn-primary" id="btn-generate" onclick="generateImages()" disabled>
        ⚡ Generate Images <span id="gen-count-badge" class="vs-count-badge"></span>
      </button>
    </div>

    <!-- Status / Error -->
    <div class="vs-status vs-hidden" id="step1-status">
      <div class="vs-spinner"></div>
      <span id="step1-status-text">Generating images...</span>
    </div>
    <div class="vs-error vs-hidden" id="step1-error">
      <span>⚠️</span>
      <span id="step1-error-text"></span>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!-- STEP 2 — IMAGE APPROVAL                                               -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div class="vs-step vs-hidden" id="step2">
    <div class="vs-step-label">Step 2 of 4</div>
    <div class="vs-step-title">Approve Images</div>

    <div class="vs-gallery-meta" id="gallery-meta">
      <span><span class="dot-total">●</span> <span id="meta-total">0</span> generated</span>
      <span><span class="dot-approved">●</span> <span id="meta-approved">0</span> approved</span>
      <span><span class="dot-rejected">●</span> <span id="meta-rejected">0</span> rejected</span>
      <span><span class="dot-pending">●</span> <span id="meta-pending">0</span> pending</span>
    </div>

    <div class="vs-gallery" id="image-gallery"></div>

    <div class="vs-step2-actions">
      <button class="vs-btn vs-btn-success" id="btn-create-videos" onclick="createVideos()" disabled>
        🎬 Create Videos <span id="approved-badge"></span>
      </button>
      <button class="vs-btn vs-btn-outline" onclick="resetToStep1()">↩ Start Over</button>
    </div>

    <div class="vs-status vs-hidden" id="step2-status">
      <div class="vs-spinner"></div>
      <span id="step2-status-text">Creating video...</span>
    </div>
    <div class="vs-progress-wrap vs-hidden" id="step2-progress">
      <div class="vs-progress-label" id="step2-progress-label">Video 0 of 0</div>
      <div class="vs-progress-bar"><div class="vs-progress-fill" id="step2-progress-fill"></div></div>
    </div>
    <div class="vs-error vs-hidden" id="step2-error">
      <span>⚠️</span>
      <span id="step2-error-text"></span>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!-- STEP 3 — CUSTOMIZE VIDEOS                                             -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div class="vs-step vs-hidden" id="step3">
    <div class="vs-step-label">Step 3 of 4</div>
    <div class="vs-step-title">Customize Your Videos</div>

    <!-- Music upload -->
    <div class="vs-music-section">
      <div class="vs-section-subtitle">🎵 Background Music (applies to all videos)</div>
      <div class="vs-file-row">
        <label class="vs-file-label">
          <input type="file" id="music-file-input" accept="audio/*,.mp3,.m4a,.wav,.aac" onchange="handleMusicUpload(this)">
          📎 Upload MP3
        </label>
        <span class="vs-filename empty" id="music-filename">No file selected</span>
      </div>
      <div class="vs-status vs-hidden" id="music-upload-status">
        <div class="vs-spinner"></div>
        <span id="music-upload-status-text">Uploading music...</span>
      </div>
      <div class="vs-error vs-hidden" id="music-upload-error">
        <span>⚠️</span>
        <span id="music-upload-error-text"></span>
      </div>
    </div>

    <!-- Title & Caption -->
    <div class="vs-customize-grid">
      <div class="vs-field">
        <label class="vs-field-label" for="video-title">📌 Title (applied to all videos)</label>
        <input type="text" id="video-title" placeholder="e.g. Mbusiness Branding AI — Transform Your Business">
      </div>
      <div class="vs-field">
        <label class="vs-field-label" for="video-caption">💬 Caption (applied to all videos)</label>
        <input type="text" id="video-caption" placeholder="e.g. Ready to grow your business? Book a free call today! 🚀">
      </div>
    </div>

    <!-- Video preview grid -->
    <div class="vs-section-subtitle">Videos to Process (<span id="videos-count">0</span>)</div>
    <div class="vs-video-gallery" id="video-gallery"></div>

    <!-- Actions -->
    <button class="vs-btn vs-btn-primary" id="btn-process-videos" onclick="processVideos()">
      ⚙️ Process Videos
    </button>

    <div class="vs-status vs-hidden" id="step3-status">
      <div class="vs-spinner"></div>
      <span id="step3-status-text">Processing video...</span>
    </div>
    <div class="vs-progress-wrap vs-hidden" id="step3-progress">
      <div class="vs-progress-label" id="step3-progress-label">Video 0 of 0</div>
      <div class="vs-progress-bar"><div class="vs-progress-fill" id="step3-progress-fill"></div></div>
    </div>
    <div class="vs-error vs-hidden" id="step3-error">
      <span>⚠️</span>
      <span id="step3-error-text"></span>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!-- STEP 4 — DOWNLOADS                                                    -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div class="vs-step vs-hidden" id="step4">
    <div class="vs-step-label">Step 4 of 4</div>
    <div class="vs-step-title">Download Your Videos ✅</div>

    <div class="vs-success-banner" id="step4-success-banner">
      ✅ <span id="step4-success-text">All videos processed successfully!</span>
    </div>

    <div class="vs-download-list" id="download-list"></div>

    <button class="vs-btn vs-btn-outline" onclick="resetAll()">🔄 Start New Batch</button>
  </div>

</div><!-- #vs-app -->

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   CONFIGURATION
   ═══════════════════════════════════════════════════════════════════════════ */
const N8N_BASE = 'https://n8n.mbusinessbrandingai.com/webhook';
const ENDPOINTS = {
  imgGenerate  : `${N8N_BASE}/img-generate`,
  vidGenerate  : `${N8N_BASE}/vid-generate`,
  vidProcess   : `${N8N_BASE}/vid-process`,
  uploadMusic  : `${N8N_BASE}/upload-music`,
};

/* ═══════════════════════════════════════════════════════════════════════════
   STATE
   ═══════════════════════════════════════════════════════════════════════════ */
let state = {
  activeTab       : 'type',
  descriptions    : [],       // string[]
  sessionId       : null,     // string
  images          : [],       // [{id, description, url, status, approved}]
  videos          : [],       // [{image_url, video_url, description}]
  musicFileId     : null,     // string (from upload-music webhook)
  musicFileName   : null,     // display name
  processedVideos : [],       // [{video_url, download_url, description, title}]
  csvDescriptions : [],       // string[] — parsed from CSV file
};

/* ═══════════════════════════════════════════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════════════════════════════════════════ */
function show(id)  { const el = document.getElementById(id); if(el) el.classList.remove('vs-hidden'); }
function hide(id)  { const el = document.getElementById(id); if(el) el.classList.add('vs-hidden'); }
function setText(id, text) { const el = document.getElementById(id); if(el) el.textContent = text; }

function showStatus(statusId, message) {
  show(statusId);
  const textId = statusId + '-text';
  setText(textId, message);
}
function hideStatus(statusId) { hide(statusId); }

function showError(errorId, message) {
  show(errorId);
  const textId = errorId + '-text';
  setText(textId, message);
}
function hideError(errorId) { hide(errorId); }

function generateSessionId() {
  return 'vs_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function setProgress(fillId, labelId, current, total) {
  const pct = total > 0 ? Math.round((current / total) * 100) : 0;
  const fill = document.getElementById(fillId);
  if (fill) fill.style.width = pct + '%';
  if (labelId) setText(labelId, `Video ${current} of ${total}`);
}

/* ═══════════════════════════════════════════════════════════════════════════
   TAB SWITCHING
   ═══════════════════════════════════════════════════════════════════════════ */
function switchTab(tab) {
  state.activeTab = tab;
  document.getElementById('tab-type').classList.toggle('active', tab === 'type');
  document.getElementById('tab-csv').classList.toggle('active',  tab === 'csv');
  document.getElementById('panel-type').style.display = (tab === 'type') ? '' : 'none';
  document.getElementById('panel-csv').style.display  = (tab === 'csv')  ? '' : 'none';
  updateDescCount();
}

/* ═══════════════════════════════════════════════════════════════════════════
   DESCRIPTION PARSING
   ═══════════════════════════════════════════════════════════════════════════ */
function parseDescriptions() {
  if (state.activeTab === 'type') {
    const raw = document.getElementById('descriptions-textarea').value;
    return raw
      .split('\n')
      .map(line => line.trim())
      .filter(line => line.length > 0);
  } else {
    return state.csvDescriptions.filter(d => d.length > 0);
  }
}

function updateDescCount() {
  const descs = parseDescriptions();
  const btn   = document.getElementById('btn-generate');
  const badge = document.getElementById('gen-count-badge');
  if (descs.length > 0) {
    btn.disabled = false;
    badge.textContent = `(${descs.length})`;
  } else {
    btn.disabled = true;
    badge.textContent = '';
  }
}

/* ═══════════════════════════════════════════════════════════════════════════
   CSV PARSING
   ═══════════════════════════════════════════════════════════════════════════ */
function parseCSV(text) {
  const lines = text.split(/\r?\n/);
  const results = [];
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    // Take first field (before first comma), strip surrounding quotes
    let field = line.split(',')[0].trim();
    field = field.replace(/^["']|["']$/g, '').trim();
    if (!field) continue;
    // Skip header row if it looks like a header label
    if (i === 0 && /^(description|prompt|text|image|title)$/i.test(field)) continue;
    results.push(field);
  }
  return results;
}

function handleCsvUpload(input) {
  hideError('step1-error');
  const file = input.files[0];
  if (!file) return;

  const nameEl = document.getElementById('csv-filename');
  nameEl.classList.remove('empty');
  nameEl.textContent = '⏳ Reading...';

  const reader = new FileReader();
  reader.onload = function(e) {
    try {
      const parsed = parseCSV(e.target.result);
      state.csvDescriptions = parsed;

      nameEl.textContent = `✓ ${file.name}`;
      nameEl.classList.remove('empty');

      // Show preview
      const previewEl = document.getElementById('csv-preview');
      const listEl    = document.getElementById('csv-preview-list');
      const countEl   = document.getElementById('csv-count');
      previewEl.style.display = '';
      countEl.textContent = parsed.length;
      listEl.innerHTML = parsed.slice(0, 8).map((d, i) =>
        `<li style="padding:4px 0; font-size:13px; color:#9ca3af; border-bottom:1px solid #1a1a1a;">
          <span style="color:#6b7280; margin-right:8px;">${i + 1}.</span>${escapeHtml(d)}
        </li>`
      ).join('') + (parsed.length > 8 ? `<li style="padding:4px 0; font-size:12px; color:#6b7280;">...and ${parsed.length - 8} more</li>` : '');

      updateDescCount();
    } catch (err) {
      nameEl.textContent = '✗ Parse error';
      nameEl.classList.add('empty');
      showError('step1-error', 'Could not parse CSV: ' + err.message);
      console.error('CSV parse error:', err);
    }
  };
  reader.onerror = function() {
    nameEl.textContent = '✗ Read error';
    showError('step1-error', 'Could not read the CSV file. Please try again.');
  };
  reader.readAsText(file);
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 1 — GENERATE IMAGES
   ═══════════════════════════════════════════════════════════════════════════ */
async function generateImages() {
  hideError('step1-error');

  const descriptions = parseDescriptions();
  if (descriptions.length === 0) {
    showError('step1-error', 'Please enter at least one image description.');
    return;
  }

  state.descriptions = descriptions;
  state.sessionId    = generateSessionId();
  state.images       = [];

  document.getElementById('btn-generate').disabled = true;
  showStatus('step1-status',
    `⏳ Generating ${descriptions.length} image${descriptions.length !== 1 ? 's' : ''}... this takes ~30 seconds per image`
  );

  try {
    const response = await fetch(ENDPOINTS.imgGenerate, {
      method  : 'POST',
      mode    : 'cors',
      headers : { 'Content-Type': 'application/json' },
      body    : JSON.stringify({
        session_id   : state.sessionId,
        descriptions : descriptions,
      }),
    });

    if (!response.ok) {
      const errText = await response.text().catch(() => '');
      throw new Error(`Server returned ${response.status}: ${errText || response.statusText}`);
    }

    const data = await response.json();
    hideStatus('step1-status');
    document.getElementById('btn-generate').disabled = false;

    // Normalise response — accept {images:[...]} or array at root
    const imagesRaw = Array.isArray(data) ? data : (data.images || []);
    if (!imagesRaw || imagesRaw.length === 0) {
      throw new Error('No images returned from the server. Check the n8n workflow response.');
    }

    state.sessionId = data.session_id || state.sessionId;
    state.images = imagesRaw.map((img, idx) => ({
      id          : img.id || `img_${idx}`,
      description : img.description || descriptions[idx] || '',
      url         : img.url || img.image_url || '',
      status      : img.status || 'pending',
      approved    : null, // null = pending, true = approved, false = rejected
    }));

    renderGallery();
    show('step2');
    document.getElementById('step2').scrollIntoView({ behavior: 'smooth', block: 'start' });

  } catch (err) {
    hideStatus('step1-status');
    document.getElementById('btn-generate').disabled = false;
    showError('step1-error', err.message);
    console.error('Image generation error:', err);
  }
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 2 — IMAGE GALLERY & APPROVAL
   ═══════════════════════════════════════════════════════════════════════════ */
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function renderGallery() {
  const gallery  = document.getElementById('image-gallery');
  gallery.innerHTML = '';

  state.images.forEach(img => {
    let stateClass = 'state-pending';
    let badgeClass = 'badge-pending';
    let badgeText  = 'Pending';
    if (img.approved === true)  { stateClass = 'state-approved'; badgeClass = 'badge-approved'; badgeText = 'Approved'; }
    if (img.approved === false) { stateClass = 'state-rejected'; badgeClass = 'badge-rejected'; badgeText = 'Rejected'; }

    const imgHtml = img.url
      ? `<img src="${escapeHtml(img.url)}" alt="${escapeHtml(img.description)}" loading="lazy" onerror="this.parentElement.innerHTML='<span class=vs-img-placeholder>🖼</span>'">`
      : `<span class="vs-img-placeholder">🖼</span>`;

    const card = document.createElement('div');
    card.className  = `vs-img-card ${stateClass}`;
    card.id         = `img-card-${img.id}`;
    card.innerHTML  = `
      <div class="vs-img-wrap">
        ${imgHtml}
        <span class="vs-img-status-badge ${badgeClass}" id="badge-${img.id}">${badgeText}</span>
      </div>
      <div class="vs-img-body">
        <div class="vs-img-desc">${escapeHtml(img.description)}</div>
        <div class="vs-img-actions">
          <button class="btn-approve" onclick="toggleApproval('${img.id}', true)"  title="Approve">✅</button>
          <button class="btn-reject"  onclick="toggleApproval('${img.id}', false)" title="Reject">❌</button>
        </div>
      </div>`;
    gallery.appendChild(card);
  });

  updateGalleryMeta();
}

function toggleApproval(imageId, approved) {
  const img = state.images.find(i => i.id === imageId);
  if (!img) return;

  // Toggle: clicking the same state un-selects it
  if (img.approved === approved) {
    img.approved = null;
  } else {
    img.approved = approved;
  }

  const card  = document.getElementById(`img-card-${imageId}`);
  const badge = document.getElementById(`badge-${imageId}`);
  if (!card || !badge) return;

  card.classList.remove('state-approved', 'state-rejected', 'state-pending');
  badge.classList.remove('badge-approved', 'badge-rejected', 'badge-pending');

  if (img.approved === true)  {
    card.classList.add('state-approved');
    badge.classList.add('badge-approved');
    badge.textContent = 'Approved';
  } else if (img.approved === false) {
    card.classList.add('state-rejected');
    badge.classList.add('badge-rejected');
    badge.textContent = 'Rejected';
  } else {
    card.classList.add('state-pending');
    badge.classList.add('badge-pending');
    badge.textContent = 'Pending';
  }

  updateGalleryMeta();
}

function updateGalleryMeta() {
  const total    = state.images.length;
  const approved = state.images.filter(i => i.approved === true).length;
  const rejected = state.images.filter(i => i.approved === false).length;
  const pending  = state.images.filter(i => i.approved === null).length;

  setText('meta-total',    total);
  setText('meta-approved', approved);
  setText('meta-rejected', rejected);
  setText('meta-pending',  pending);

  const btn   = document.getElementById('btn-create-videos');
  const badge = document.getElementById('approved-badge');
  btn.disabled = approved === 0;
  badge.textContent = approved > 0 ? `(${approved} approved)` : '';
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 2 → STEP 3 — CREATE VIDEOS (sequential)
   ═══════════════════════════════════════════════════════════════════════════ */
async function createVideos() {
  hideError('step2-error');

  const approved = state.images.filter(i => i.approved === true);
  if (approved.length === 0) {
    showError('step2-error', 'Please approve at least one image before creating videos.');
    return;
  }

  document.getElementById('btn-create-videos').disabled = true;
  state.videos = [];

  show('step2-status');
  show('step2-progress');
  setProgress('step2-progress-fill', 'step2-progress-label', 0, approved.length);

  for (let i = 0; i < approved.length; i++) {
    const img = approved[i];
    showStatus('step2-status',
      `🎬 Creating video ${i + 1} of ${approved.length}... (~60-90 seconds each)`
    );
    setProgress('step2-progress-fill', 'step2-progress-label', i, approved.length);

    try {
      const response = await fetch(ENDPOINTS.vidGenerate, {
        method  : 'POST',
        mode    : 'cors',
        headers : { 'Content-Type': 'application/json' },
        body    : JSON.stringify({
          image_url : img.url,
          prompt    : img.description,
        }),
      });

      if (!response.ok) {
        const errText = await response.text().catch(() => '');
        throw new Error(`Video ${i + 1} failed — server returned ${response.status}: ${errText || response.statusText}`);
      }

      const data = await response.json();
      const videoUrl = data.video_url || data.url || '';
      if (!videoUrl) {
        throw new Error(`Video ${i + 1}: no video_url in response. Check n8n workflow WF2.`);
      }

      state.videos.push({
        image_url   : img.url,
        video_url   : videoUrl,
        description : img.description,
      });

    } catch (err) {
      hideStatus('step2-status');
      hide('step2-progress');
      document.getElementById('btn-create-videos').disabled = false;
      showError('step2-error', err.message);
      console.error('Video creation error:', err);
      return; // stop on first failure
    }
  }

  setProgress('step2-progress-fill', 'step2-progress-label', approved.length, approved.length);
  hideStatus('step2-status');

  // Small delay so user sees 100%
  await new Promise(r => setTimeout(r, 600));
  hide('step2-progress');
  document.getElementById('btn-create-videos').disabled = false;

  renderVideoGallery();
  show('step3');
  document.getElementById('step3').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 3 — RENDER VIDEO PREVIEW GALLERY
   ═══════════════════════════════════════════════════════════════════════════ */
function renderVideoGallery() {
  const gallery = document.getElementById('video-gallery');
  gallery.innerHTML = '';
  setText('videos-count', state.videos.length);

  state.videos.forEach((vid, idx) => {
    const card = document.createElement('div');
    card.className = 'vs-video-card';
    card.innerHTML = `
      <video controls preload="metadata" src="${escapeHtml(vid.video_url)}">
        Your browser does not support the video tag.
      </video>
      <div class="vs-video-card-body">
        <div class="vs-video-card-desc">${escapeHtml(vid.description)}</div>
      </div>`;
    gallery.appendChild(card);
  });
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 3 — MUSIC UPLOAD
   ═══════════════════════════════════════════════════════════════════════════ */
async function handleMusicUpload(input) {
  hideError('music-upload-error');

  const file = input.files[0];
  if (!file) return;

  const nameEl = document.getElementById('music-filename');
  nameEl.classList.remove('empty');
  nameEl.textContent = '⏳ Uploading...';

  showStatus('music-upload-status', `Uploading ${file.name}...`);

  try {
    const formData = new FormData();
    formData.append('file', file);

    // Do NOT set Content-Type header — browser sets it with the correct boundary
    const response = await fetch(ENDPOINTS.uploadMusic, {
      method : 'POST',
      mode   : 'cors',
      body   : formData,
    });

    if (!response.ok) {
      const errText = await response.text().catch(() => '');
      throw new Error(`Upload failed — server returned ${response.status}: ${errText || response.statusText}`);
    }

    const data = await response.json();
    state.musicFileId   = data.file_id || data.id || null;
    state.musicFileName = data.original_name || file.name;

    hideStatus('music-upload-status');
    nameEl.textContent = `✓ ${state.musicFileName}`;

  } catch (err) {
    hideStatus('music-upload-status');
    state.musicFileId   = null;
    state.musicFileName = null;
    nameEl.textContent  = '✗ Upload failed';
    nameEl.classList.add('empty');
    showError('music-upload-error', err.message);
    console.error('Music upload error:', err);
  }
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 3 — PROCESS VIDEOS (sequential)
   ═══════════════════════════════════════════════════════════════════════════ */
async function processVideos() {
  hideError('step3-error');

  const title   = document.getElementById('video-title').value.trim();
  const caption = document.getElementById('video-caption').value.trim();

  if (state.videos.length === 0) {
    showError('step3-error', 'No videos to process. Go back and create videos first.');
    return;
  }

  document.getElementById('btn-process-videos').disabled = true;
  state.processedVideos = [];

  show('step3-status');
  show('step3-progress');
  setProgress('step3-progress-fill', 'step3-progress-label', 0, state.videos.length);

  for (let i = 0; i < state.videos.length; i++) {
    const vid = state.videos[i];
    showStatus('step3-status', `⚙️ Processing video ${i + 1} of ${state.videos.length}...`);
    setProgress('step3-progress-fill', 'step3-progress-label', i, state.videos.length);

    try {
      const payload = {
        video_url : vid.video_url,
        caption   : caption || undefined,
        title     : title   || undefined,
      };
      if (state.musicFileId) {
        payload.music_file_id = state.musicFileId;
      }

      const response = await fetch(ENDPOINTS.vidProcess, {
        method  : 'POST',
        mode    : 'cors',
        headers : { 'Content-Type': 'application/json' },
        body    : JSON.stringify(payload),
      });

      if (!response.ok) {
        const errText = await response.text().catch(() => '');
        throw new Error(`Process video ${i + 1} failed — server returned ${response.status}: ${errText || response.statusText}`);
      }

      const data = await response.json();
      const downloadUrl = data.download_url || data.url || '';
      if (!downloadUrl) {
        throw new Error(`Video ${i + 1}: no download_url in response. Check n8n workflow WF3.`);
      }

      state.processedVideos.push({
        video_url    : vid.video_url,
        download_url : downloadUrl,
        description  : vid.description,
        title        : title || `Video ${i + 1}`,
      });

    } catch (err) {
      hideStatus('step3-status');
      hide('step3-progress');
      document.getElementById('btn-process-videos').disabled = false;
      showError('step3-error', err.message);
      console.error('Video processing error:', err);
      return;
    }
  }

  setProgress('step3-progress-fill', 'step3-progress-label', state.videos.length, state.videos.length);
  hideStatus('step3-status');

  await new Promise(r => setTimeout(r, 600));
  hide('step3-progress');
  document.getElementById('btn-process-videos').disabled = false;

  renderDownloads();
  show('step4');
  document.getElementById('step4').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ═══════════════════════════════════════════════════════════════════════════
   STEP 4 — RENDER DOWNLOAD LIST
   ═══════════════════════════════════════════════════════════════════════════ */
function renderDownloads() {
  const list = document.getElementById('download-list');
  list.innerHTML = '';

  const count = state.processedVideos.length;
  setText('step4-success-text',
    `${count} video${count !== 1 ? 's' : ''} processed successfully and ready to download!`
  );

  state.processedVideos.forEach((vid, idx) => {
    const item = document.createElement('div');
    item.className = 'vs-download-item';

    const displayTitle = vid.title && vid.title !== `Video ${idx + 1}`
      ? vid.title
      : `Video ${idx + 1}`;

    item.innerHTML = `
      <div class="vs-download-item-info">
        <span class="vs-download-icon">🎬</span>
        <div>
          <div class="vs-download-title">${escapeHtml(displayTitle)}</div>
          <div class="vs-download-desc">${escapeHtml(vid.description)}</div>
        </div>
      </div>
      <div class="vs-download-actions">
        <button class="vs-btn vs-btn-ghost vs-btn-sm" onclick="previewVideo(${idx})">▶ Preview</button>
        <a class="vs-btn vs-btn-success vs-btn-sm" href="${escapeHtml(vid.download_url)}" download="${escapeHtml(displayTitle)}.mp4" target="_blank">⬇ Download</a>
      </div>`;
    list.appendChild(item);
  });
}

function previewVideo(idx) {
  const vid = state.processedVideos[idx];
  if (!vid) return;
  // Open in new tab for preview
  window.open(vid.download_url, '_blank', 'noopener,noreferrer');
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESET
   ═══════════════════════════════════════════════════════════════════════════ */
function resetToStep1() {
  hide('step2');
  hide('step3');
  hide('step4');
  hideError('step1-error');
  hideStatus('step1-status');
  document.getElementById('btn-generate').disabled = false;
  state.images = [];
  state.videos = [];
  state.processedVideos = [];
  updateDescCount();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetAll() {
  // Full reset
  state = {
    activeTab       : 'type',
    descriptions    : [],
    sessionId       : null,
    images          : [],
    videos          : [],
    musicFileId     : null,
    musicFileName   : null,
    processedVideos : [],
    csvDescriptions : [],
  };

  document.getElementById('descriptions-textarea').value = '';
  document.getElementById('video-title').value   = '';
  document.getElementById('video-caption').value = '';

  const csvInput   = document.getElementById('csv-file-input');
  const musicInput = document.getElementById('music-file-input');
  if (csvInput)   csvInput.value   = '';
  if (musicInput) musicInput.value = '';

  setText('csv-filename',   'No file selected');
  setText('music-filename', 'No file selected');
  document.getElementById('csv-filename').classList.add('empty');
  document.getElementById('music-filename').classList.add('empty');
  document.getElementById('csv-preview').style.display = 'none';

  hide('step2');
  hide('step3');
  hide('step4');

  hideError('step1-error');
  hideError('step2-error');
  hideError('step3-error');
  hideError('music-upload-error');
  hideStatus('step1-status');
  hideStatus('step2-status');
  hideStatus('step3-status');
  hide('step2-progress');
  hide('step3-progress');

  document.getElementById('btn-generate').disabled = true;
  document.getElementById('gen-count-badge').textContent = '';
  switchTab('type');

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ═══════════════════════════════════════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
  updateDescCount();
  // Bind textarea live update
  document.getElementById('descriptions-textarea').addEventListener('input', updateDescCount);
});
</script>
<?php get_footer(); ?>
