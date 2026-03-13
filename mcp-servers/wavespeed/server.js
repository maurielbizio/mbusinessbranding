#!/usr/bin/env node
/**
 * Wavespeed AI MCP Server
 * Generates images and videos via the Wavespeed AI REST API.
 */

import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";
import fetch from "node-fetch";

const API_KEY = process.env.WAVESPEED_API_KEY || "";
const BASE_URL = "https://api.wavespeed.ai/api/v3";

const server = new McpServer({
  name: "wavespeed",
  version: "1.0.0",
});

function getHeaders() {
  return {
    Authorization: `Bearer ${API_KEY}`,
    "Content-Type": "application/json",
  };
}

function apiError(msg) {
  return { content: [{ type: "text", text: `Error: ${msg}` }] };
}

function checkKey() {
  if (!API_KEY) return "WAVESPEED_API_KEY environment variable not set.";
  return null;
}

// ─── generate_image ───────────────────────────────────────────────────────────

server.tool(
  "generate_image",
  "Generate an image from a text prompt using Wavespeed AI (Flux Dev model). Returns a task_id — poll with get_task_status to get output URLs.",
  {
    prompt: z.string().describe("Text description of the image to generate."),
    size: z.string().default("1024*1024").describe('Image dimensions e.g. "1024*1024", "768*1024", "1024*768".'),
    num_images: z.number().int().min(1).max(4).default(1).describe("Number of images to generate (1-4)."),
  },
  async ({ prompt, size, num_images }) => {
    const err = checkKey();
    if (err) return apiError(err);
    if (!prompt.trim()) return apiError("prompt cannot be empty.");

    const payload = {
      inputs: { prompt, size, num_images },
      enable_safety_checker: true,
    };

    try {
      const r = await fetch(`${BASE_URL}/wavespeed-ai/flux-dev/text-to-image`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify(payload),
      });
      const json = await r.json();
      if (!r.ok) return apiError(`HTTP ${r.status}: ${JSON.stringify(json).slice(0, 300)}`);
      const data = json.data || {};
      const task_id = data.id || "";
      if (!task_id) return apiError(`No task ID returned: ${JSON.stringify(json).slice(0, 300)}`);
      return {
        content: [{
          type: "text",
          text: `Image generation submitted.\ntask_id: ${task_id}\nstatus: ${data.status || "created"}\nUse get_task_status('${task_id}') to check progress and get output URLs.`,
        }],
      };
    } catch (e) {
      return apiError(e.message);
    }
  }
);

// ─── generate_video_from_text ─────────────────────────────────────────────────

server.tool(
  "generate_video_from_text",
  "Generate a video from a text prompt using Wavespeed AI (Wan T2V model). Returns a task_id — poll with get_task_status to get output URLs.",
  {
    prompt: z.string().describe("Text description of the video to generate."),
    duration: z.number().int().min(1).max(10).default(5).describe("Video duration in seconds (1-10)."),
    size: z.string().default("480*832").describe('Video dimensions e.g. "480*832" (portrait) or "832*480" (landscape).'),
  },
  async ({ prompt, duration, size }) => {
    const err = checkKey();
    if (err) return apiError(err);
    if (!prompt.trim()) return apiError("prompt cannot be empty.");

    const payload = {
      inputs: { prompt, duration, size },
      enable_safety_checker: true,
    };

    try {
      const r = await fetch(`${BASE_URL}/wavespeed-ai/wan-t2v-480p`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify(payload),
      });
      const json = await r.json();
      if (!r.ok) return apiError(`HTTP ${r.status}: ${JSON.stringify(json).slice(0, 300)}`);
      const data = json.data || {};
      const task_id = data.id || "";
      if (!task_id) return apiError(`No task ID returned: ${JSON.stringify(json).slice(0, 300)}`);
      return {
        content: [{
          type: "text",
          text: `Video generation (text→video) submitted.\ntask_id: ${task_id}\nstatus: ${data.status || "created"}\nUse get_task_status('${task_id}') to check progress and get output URLs.`,
        }],
      };
    } catch (e) {
      return apiError(e.message);
    }
  }
);

// ─── generate_video_from_image ────────────────────────────────────────────────

server.tool(
  "generate_video_from_image",
  "Animate an image into a video using Wavespeed AI (Wan I2V 720p model). Returns a task_id — poll with get_task_status to get output URLs.",
  {
    image_url: z.string().url().describe("URL of the source image to animate."),
    prompt: z.string().default("").describe("Optional text prompt to guide motion/style."),
    duration: z.number().int().min(1).max(10).default(5).describe("Video duration in seconds (1-10)."),
  },
  async ({ image_url, prompt, duration }) => {
    const err = checkKey();
    if (err) return apiError(err);

    const inputs = { image: image_url, duration };
    if (prompt.trim()) inputs.prompt = prompt;

    const payload = { inputs, enable_safety_checker: true };

    try {
      const r = await fetch(`${BASE_URL}/wavespeed-ai/wan-i2v-720p`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify(payload),
      });
      const json = await r.json();
      if (!r.ok) return apiError(`HTTP ${r.status}: ${JSON.stringify(json).slice(0, 300)}`);
      const data = json.data || {};
      const task_id = data.id || "";
      if (!task_id) return apiError(`No task ID returned: ${JSON.stringify(json).slice(0, 300)}`);
      return {
        content: [{
          type: "text",
          text: `Video generation (image→video) submitted.\ntask_id: ${task_id}\nstatus: ${data.status || "created"}\nUse get_task_status('${task_id}') to check progress and get output URLs.`,
        }],
      };
    } catch (e) {
      return apiError(e.message);
    }
  }
);

// ─── get_task_status ──────────────────────────────────────────────────────────

server.tool(
  "get_task_status",
  "Check the status of a Wavespeed AI generation task. Returns status and output URLs when complete.",
  {
    task_id: z.string().describe("The task ID returned by generate_image or generate_video tools."),
  },
  async ({ task_id }) => {
    const err = checkKey();
    if (err) return apiError(err);
    if (!task_id.trim()) return apiError("task_id cannot be empty.");

    try {
      const r = await fetch(`${BASE_URL}/predictions/${task_id}`, {
        headers: getHeaders(),
      });
      const json = await r.json();
      if (!r.ok) return apiError(`HTTP ${r.status}: ${JSON.stringify(json).slice(0, 300)}`);
      const data = json.data || {};
      const status = data.status || "unknown";
      const outputs = data.outputs || [];
      const error = data.error || "";

      const lines = [`task_id: ${task_id}`, `status: ${status}`];
      if (outputs.length) {
        lines.push("outputs:");
        outputs.forEach((url) => lines.push(`  - ${url}`));
      }
      if (error) lines.push(`error: ${error}`);
      if (["created", "processing"].includes(status)) {
        lines.push("(Still processing — poll again in a few seconds.)");
      }

      return { content: [{ type: "text", text: lines.join("\n") }] };
    } catch (e) {
      return apiError(e.message);
    }
  }
);

// ─── Start ────────────────────────────────────────────────────────────────────

const transport = new StdioServerTransport();
await server.connect(transport);
