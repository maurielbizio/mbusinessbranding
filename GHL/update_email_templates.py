#!/usr/bin/env python3
"""Update all 40 business-type email templates in GHL with correct HTML content."""

import json
import time
import urllib.request
import urllib.error

API_KEY = "pit-f99b04c2-cb21-433b-8934-c642fd27a5c9"
LOCATION_ID = "UsI5TMVazJW2JlhyR0WH"
STRATEGY_LINK = "{{trigger_link.h1zRc64nntbXDFn4F8Wg}}"

def build_html(subject, preheader, body_html, cta_text="Book a Free Strategy Call"):
    return f"""<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{subject}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;">
<tr><td align="center" style="padding:30px 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
<tr><td style="background-color:#1a1a2e;padding:28px 40px;">
<h1 style="color:#ffffff;margin:0;font-size:20px;font-weight:700;letter-spacing:0.5px;">Mbusiness Branding AI</h1>
</td></tr>
<tr><td style="padding:40px 40px 10px;">
<p style="color:#333333;font-size:16px;line-height:1.7;margin:0 0 18px;">Hi {{{{contact.first_name}}}},</p>
{body_html}
<table width="100%" cellpadding="0" cellspacing="0"><tr>
<td align="center" style="padding:20px 0 30px;">
<a href="{STRATEGY_LINK}" style="background-color:#e63946;color:#ffffff;text-decoration:none;padding:16px 36px;border-radius:6px;font-size:16px;font-weight:700;display:inline-block;">{cta_text}</a>
</td></tr></table>
<p style="color:#333333;font-size:15px;line-height:1.6;margin:0 0 4px;">— Mauriel</p>
<p style="color:#666666;font-size:14px;margin:0;">Mbusiness Branding AI</p>
</td></tr>
<tr><td style="background-color:#f8f8f8;padding:20px 40px;border-top:1px solid #eeeeee;">
<p style="color:#999999;font-size:12px;margin:0;text-align:center;">You received this because you requested free content from Mbusiness Branding AI.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>"""

def p(text):
    return f'<p style="color:#333333;font-size:16px;line-height:1.7;margin:0 0 18px;">{text}</p>'

def h2(text):
    return f'<h2 style="color:#1a1a2e;font-size:18px;margin:0 0 14px;">{text}</h2>'

def ul(items):
    lis = "".join(f'<li style="color:#333333;font-size:16px;line-height:1.7;margin-bottom:8px;">{i}</li>' for i in items)
    return f'<ul style="margin:0 0 18px;padding-left:24px;">{lis}</ul>'

# ─────────────────────────────────────────────
# ALL 40 TEMPLATES
# ─────────────────────────────────────────────

TEMPLATES = {

    # ── EMAIL 05 ── Day 3 — Pain Point: Competitor Winning ──────────────────

    "69b1715752f4384c68e275e3": {
        "subject": "The plumber down the street just booked your next job",
        "preheader": "While you were under a sink, they answered the phone",
        "body": (
            p("While you were on a job this week, someone called your business. You couldn't answer. They called the next plumber on their list — and booked with them.") +
            p("This happens dozens of times a month for plumbers who don't have a system handling their phones. The shops that are growing right now aren't necessarily better plumbers. They just respond faster.") +
            p("Customers call 2–3 companies and go with whoever answers first. If your phone goes to voicemail — you're invisible, even if you're the best plumber in the area.") +
            p("An AI receptionist captures every call while you're on jobs. It answers basic questions, collects the lead's info, and books them on your calendar. You just show up.") +
            p("If you want to stop losing jobs to whoever answers faster — let's talk.")
        ),
    },

    "69b17218c99bdc1b5baee17a": {
        "subject": "HVAC peak season — are you capturing every call?",
        "preheader": "Customers call 3 companies and go with whoever responds first",
        "body": (
            p("Summer is here. Your phone is ringing constantly. You're already booked out 2 weeks — and you're still missing calls.") +
            p("Here's what's happening with every call you miss: that homeowner opens their phone, calls the next HVAC company on the list, and books whoever picks up. Peak season revenue is walking out the door because you can't be in the field and on the phone at the same time.") +
            p("This is exactly what AI was built to solve. Every overflow call gets answered, captured, and routed — even at 10pm during a heat wave. Maintenance appointments get booked during your slower months while you focus on the rush jobs.") +
            p("You're already doing the hard part. Let's make sure no call goes unanswered when it counts most.")
        ),
    },

    "69b172232d6bd612979b8ad7": {
        "subject": "The estimate you gave last week — did they go with you?",
        "preheader": "Most roofing estimates go cold because follow-up stops at day 1",
        "body": (
            p("You gave a free estimate. Did the job. Sent the quote. Followed up once. Silence.") +
            p("Meanwhile, the roofing company that followed up on day 2, day 5, and day 10 just got the contract.") +
            p("Roofing estimates are high-ticket. Homeowners take time. They get 3 quotes, think about it, talk to their spouse, and decide somewhere in days 3–10. The company that wins isn't usually the cheapest or the best — it's the one that stayed in front of them during that window.") +
            p("Automated follow-up sequences do this without you lifting a finger. Every estimate gets touched at the right intervals until the prospect decides. No more jobs going cold because you got busy.") +
            p("Ready to close more of the estimates you're already giving?")
        ),
    },

    "69b17226c99bdc1089aee257": {
        "subject": "Why your neighbor hired someone else for their lawn",
        "preheader": "The company with 50 Google reviews got the call — not you",
        "body": (
            p("Spring is here. Homeowners are searching 'lawn care near me.' And the company that shows up first — with the most reviews and the most social presence — gets the call.") +
            p("If that's not you, you're invisible — even if you do better work than whoever they hired.") +
            p("Seasonal businesses are tough because you have to re-win customers every single year. The landscaping companies that grow consistently are the ones that stayed visible all winter: social posts in January and February, re-booking texts in March, review requests after every fall cleanup.") +
            p("By the time their competitors are fighting for spring clients, these businesses already have them locked in.") +
            p("That system runs automatically. Let me show you what it looks like for your business.")
        ),
    },

    "69b17228c99bdc8e51aee275": {
        "subject": "Why price shoppers are not your real problem",
        "preheader": "The real problem is one-time clients who never come back",
        "body": (
            p("Price shoppers are frustrating. But they're not why your revenue is stuck.") +
            p("The real drain is clients who book once — and never come back. Not because they were unhappy. Because nobody reminded them it was time to rebook.") +
            p("The cleaning businesses growing fastest right now have a simple system:") +
            ul([
                "Post-service review requests build their Google reputation so new clients find them",
                "Re-booking follow-ups turn one-time bookings into recurring clients",
                "Referral prompts turn happy clients into their best sales reps",
            ]) +
            p("None of this requires you to personally send anything. It all runs in the background. You don't need more price shoppers — you need a system that keeps the good clients coming back.")
        ),
    },

    "69b1722b23f469b410faf97c": {
        "subject": "The referral that went to another contractor this week",
        "preheader": "When you're heads-down on a project, your pipeline quietly dries up",
        "body": (
            p("You're deep in a project. Calls are going to voicemail. A homeowner saw your work and wanted to hire you — but couldn't reach you. They called a contractor who had before/after photos on Instagram, 40 Google reviews, and responded within an hour.") +
            p("That contractor might do the same quality of work as you. But they look like the obvious choice — and they have a system to answer when they're on the job site.") +
            p("This is the GC problem: every big project takes you off the market for months. By the time you surface, your pipeline is empty and you're starting over.") +
            p("An AI receptionist captures every lead that comes in while you're working. Social content keeps your business visible even when you're heads-down. You surface from a project to a full pipeline instead of an empty one.") +
            p("Let's talk about what this looks like for your business.")
        ),
    },

    "69b1722da8e9666322f86989": {
        "subject": "The chair that was empty because no one sent a reminder",
        "preheader": "No-shows and drifting clients are costing you more than you realize",
        "body": (
            p("A no-show hits you immediately — empty chair, lost revenue. But the slower damage is the client who 'went somewhere else.' Not because they didn't like you. Because they got busy, drifted a few weeks, and the shop down the street texted a rebooking link before you did.") +
            p("The barbers and stylists who keep full books aren't just talented — they have a system:") +
            ul([
                "Appointment reminders 24 hours and 2 hours out that cut no-shows significantly",
                "\"Ready to book again?\" texts at the 3-week mark so clients come back before they wander",
                "Birthday and loyalty touchpoints that make clients feel remembered",
            ]) +
            p("These run automatically. You focus on the cut and the conversation. The system handles the business side.") +
            p("One call — let me show you what this looks like.")
        ),
    },

    "69b1722f6e6e10954aecd15f": {
        "subject": "Why your customers keep going back to the chain",
        "preheader": "Jiffy Lube sends a reminder at 3 months. You don't. That's why.",
        "body": (
            p("You do better work. You know your customers by name. They still go to the chain for their next oil change.") +
            p("Why? Because Jiffy Lube sends a text at 3 months reminding them it's due. And you don't.") +
            p("You're not losing to them on quality. You're losing to them on follow-up — and they've spent $20 million building a CRM to do it.") +
            p("You don't need a $20 million CRM. You need:") +
            ul([
                "AI maintenance reminders at 3, 6, and 12 months based on the service performed",
                "Review requests after service that build the Google rating new customers check",
                "Social content that shows your expertise and keeps regulars remembering you exist",
            ]) +
            p("Independent shops that implement this are seeing real retention recovery. One call — let me show you how it works.")
        ),
    },

    # ── EMAIL 06 ── Day 7 — Week 2 + VIP Offer ──────────────────────────────

    "69b1724552f438cd44e282f5": {
        "subject": "Week 2 content is ready — and I want to make you an offer",
        "preheader": "Week 2 posts are in your Drive + a limited offer for plumbing pros",
        "body": (
            p("Your Week 2 social media content is now in the Drive folder — 7 more posts ready to publish.") +
            p("Now that you've seen what AI-generated social content looks like for your plumbing business, I want to ask you something: what if this was running automatically, every single week, without you thinking about it?") +
            p("That's one piece of the <strong>Founders VIP Program</strong>. Along with it:") +
            ul([
                "24/7 AI receptionist that captures every lead and books appointments while you're on jobs",
                "Automated review requests that build your Google reputation after every completed job",
                "AI-powered website designed for lead capture",
            ]) +
            p("This is what the plumbing businesses winning jobs 24/7 are running.") +
            p("I'm offering Founders VIP at <strong>75% off</strong> for the first 10 clients. Spots are almost full.") +
            p("Click below to book a quick strategy call and I'll walk you through exactly how it works for your business.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b17251a514be4771252a09": {
        "subject": "Week 2 content is here — plus something important for HVAC season",
        "preheader": "Week 2 posts in Drive + Founders VIP offer for HVAC pros",
        "body": (
            p("Your Week 2 content is in the Drive folder — 7 new posts ready to publish.") +
            p("Now that you've seen what AI content looks like for HVAC, I want to talk about the bigger picture. Peak season is when you're most likely to miss calls, lose leads, and burn out — all at the same time.") +
            p("The <strong>Founders VIP Program</strong> is built specifically for this:") +
            ul([
                "AI receptionist captures every overflow call during peak season — no more missed leads when you're slammed",
                "Automated maintenance reminders keep your slow season from going dry",
                "Social content keeps you visible year-round, not just when it's 95 degrees",
            ]) +
            p("I'm offering this at <strong>75% off</strong> for the first 10 HVAC companies. HVAC spots are almost gone — peak season urgency is real.") +
            p("Book a quick call and let's go through it together.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1725d23f4698aa7fafc51": {
        "subject": "Week 2 is ready — here is what closes more roofing estimates",
        "preheader": "Week 2 posts in Drive + Founders VIP offer for roofers",
        "body": (
            p("Your Week 2 content is in the Drive folder — ready to post.") +
            p("Roofing is a high-ticket, high-trust business. Homeowners don't hire the first roofer they find — they hire the one who looks credible, has reviews, and follows up.") +
            p("The <strong>Founders VIP Program</strong> handles all three:") +
            ul([
                "Consistent social content builds trust before they even call",
                "Automated estimate follow-up sequences keep your quotes from going cold",
                "Review requests after every job build your Google rating against fly-by-night competitors",
            ]) +
            p("This is what the roofing companies closing 60–70% of their estimates are running.") +
            p("<strong>75% off</strong> for the first 10 roofers. A few spots left. Book a quick call below.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b17269d9d13c77e7da0cd5": {
        "subject": "Week 2 posts are in — plus how to lock in your spring clients now",
        "preheader": "Week 2 content ready + Founders VIP offer for landscaping businesses",
        "body": (
            p("Your Week 2 posts are in the Drive folder.") +
            p("Spring is either here or just around the corner — and the landscaping businesses that win this season locked in their clients before it hit.") +
            p("The <strong>Founders VIP Program</strong> helps you do exactly that:") +
            ul([
                "Social content keeps you visible year-round — not just when the grass is growing",
                "AI follow-up re-books existing clients automatically before they go looking for someone new",
                "Review requests build the Google profile that wins calls from homeowners who don't know you yet",
            ]) +
            p("<strong>75% off</strong> for the first 10 landscaping businesses. A few founding spots left. Let's talk.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b17273524c3aa418ae9f68": {
        "subject": "Week 2 is here — and a way to stop losing clients after the first booking",
        "preheader": "Week 2 posts in Drive + Founders VIP offer for cleaning businesses",
        "body": (
            p("Your Week 2 content is in the Drive folder — ready to publish.") +
            p("The cleaning businesses growing fastest right now aren't necessarily getting more new clients. They're keeping the ones they already have.") +
            p("The <strong>Founders VIP Program</strong> is built for retention:") +
            ul([
                "Automated re-booking follow-ups turn one-time bookings into recurring clients",
                "Review requests after every service build the Google reputation that brings new clients in",
                "Social content keeps your brand in front of people who might need you next month",
            ]) +
            p("<strong>75% off</strong> for the first 10 cleaning businesses. Spots are limited and nearly full. Book a call below.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1727e52f438250fe287a1": {
        "subject": "Week 2 posts plus how to stop the feast-or-famine cycle",
        "preheader": "Week 2 content ready + Founders VIP offer for contractors",
        "body": (
            p("Your Week 2 content is in the Drive folder.") +
            p("Now let's talk about the biggest problem in general contracting: the feast-or-famine cycle. You're slammed on a project, pipeline dries up, project ends, and you spend 2 months chasing the next job.") +
            p("The <strong>Founders VIP Program</strong> breaks that cycle:") +
            ul([
                "AI receptionist captures leads and books discovery calls even when you're on a job site",
                "Social content keeps your work visible and attracts homeowners consistently",
                "Review requests build your reputation so referrals come to you without you asking",
            ]) +
            p("<strong>75% off</strong> for the first 10 contractors. A few founding spots remaining. Book a quick call.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1728a980c39e478fda769": {
        "subject": "Week 2 is in your inbox — plus how to fill every open slot in your book",
        "preheader": "Week 2 posts ready + Founders VIP offer for barbers and stylists",
        "body": (
            p("Your Week 2 content is in the Drive folder.") +
            p("Quick question: what does an empty chair cost you? Not just today — but every week, compounded across a year?") +
            p("The <strong>Founders VIP Program</strong> is built to keep your book full:") +
            ul([
                "Appointment reminders cut no-shows — clients get reminded 24 hours and 2 hours out",
                "Re-booking texts go out at the 3-week mark so clients don't drift to another shop",
                "Review requests build your Google and Yelp profile for new clients who are searching",
                "Social content shows your work so new clients know your style before they call",
            ]) +
            p("<strong>75% off</strong> for the first 10 shops. Book a quick strategy call.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1729429a494e59958dd92": {
        "subject": "Week 2 posts ready — and how to stop losing repeat customers to chains",
        "preheader": "Week 2 content in Drive + Founders VIP offer for auto shops",
        "body": (
            p("Your Week 2 posts are in the Drive folder.") +
            p("The chains spend millions on retention technology. The <strong>Founders VIP Program</strong> lets you run the same playbook for a fraction of the cost:") +
            ul([
                "AI maintenance reminders at 3 months, 6 months, and 1 year — based on the service performed",
                "Review requests after every visit that build the Google rating new customers check before choosing",
                "Social content showing your expertise builds trust before they ever walk in",
            ]) +
            p("Independent shops that run this system see meaningful retention recovery against the chains — without a $20 million CRM budget.") +
            p("<strong>75% off</strong> for the first 10 auto shops. Let's talk.")
        ),
        "cta": "Book My Strategy Call",
    },

    # ── EMAIL 07 ── Day 9 — Objection Handler ───────────────────────────────

    "69b172ab29a4943d8358df02": {
        "subject": "I am too busy right now — that is exactly what this solves",
        "preheader": "The busiest plumbers say the same thing — here is what changed for them",
        "body": (
            p("\"I'm too busy right now.\"") +
            p("I hear this from plumbing business owners constantly. And I get it — you're in the field, running the business, dealing with everything at once.") +
            p("But here's the thing: being too busy is only a problem if new leads are still slipping through. If someone calls while you're under a sink and nobody answers — being busy just cost you a job.") +
            p("The whole point of the AI receptionist is that it takes \"busy\" off your plate. You don't manage leads. You don't answer overflow calls. You don't chase follow-ups. You just show up to the appointments on your calendar.") +
            p("If \"too busy right now\" is where you're at — this is exactly when you need this.") +
            p("One call. 20 minutes. I'll show you how it works.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172b62ba04a691495fa85": {
        "subject": "I am slammed during peak season — that is exactly when you need this",
        "preheader": "Peak season is when you lose the most leads — here is how to stop it",
        "body": (
            p("\"I'm slammed right now — I'll deal with this after the season.\"") +
            p("This is the most common thing HVAC business owners tell me. And when it's 95 degrees and you're booked 3 weeks out, I understand why adding anything new sounds impossible.") +
            p("But here's what's actually happening during that busy season: you're missing calls because you're on jobs. Those missed calls are calling the next HVAC company. The overflow is happening right now — not after the season.") +
            p("Setting up the AI receptionist takes one 20-minute call. After that, every overflow call gets captured — even during your busiest peak weeks.") +
            p("The setup is fast. The revenue recovery starts immediately. Book a call — 20 minutes.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172c2a514be03692531af": {
        "subject": "What the roofers who close more estimates do differently",
        "preheader": "It is not luck — they have a follow-up system and you do not",
        "body": (
            p("There are roofers in your market closing 60–70% of their estimates. And roofers closing 20–30%.") +
            p("The difference is almost never quality of work. It's follow-up.") +
            p("The ones closing more have a system that touches every estimate at the right intervals:") +
            ul([
                "Day 1: estimate sent + personal follow-up",
                "Day 3: \"Any questions about the estimate?\"",
                "Day 7: social proof — a customer review or completed project photo",
                "Day 10: final outreach before they move on",
            ]) +
            p("Most homeowners decide between day 3 and day 10. If you're not in their inbox during that window — whoever is gets the job.") +
            p("Automated estimate follow-up is a core piece of the Founders VIP. It runs without you touching it. One call — I'll show you.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172cd52f43832aee28bcf": {
        "subject": "The landscapers who are fully booked before spring even starts",
        "preheader": "They locked in clients in February — here is how they did it",
        "body": (
            p("The landscaping companies that open spring fully booked didn't get lucky. They stayed in front of their clients all winter.") +
            ul([
                "Social posts in January and February keeping their name visible",
                "Re-booking texts in March: \"Spring is coming — want to lock in your weekly service?\"",
                "Review requests after every fall cleanup building their Google profile",
            ]) +
            p("By the time their competitors are fighting for spring clients, these businesses already have them.") +
            p("You can build this system before next spring — and none of it requires you to do it manually. One call — let me walk you through the setup. 20 minutes.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172d8d9d13c314dda13a4": {
        "subject": "The cleaning business owners who said the same thing you are thinking",
        "preheader": "They figured out how to stop the one-and-done problem",
        "body": (
            p("Every cleaning business owner I work with says some version of this at first: \"I just need more clients.\"") +
            p("But when we dig in — the real problem isn't getting new clients. It's keeping them.") +
            p("They have happy clients who never leave a review. First-time bookings who never come back. No system for re-booking. And they're spending money on ads trying to fill a leaky bucket instead of plugging it.") +
            p("The cleaning businesses that grow don't have the most leads. They have the best retention. Re-booking follow-ups. Review requests. Referral prompts. These run automatically in the background while you focus on the work.") +
            p("One call — I'll show you the full picture.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172e452f43870d7e28cbe": {
        "subject": "What happens to your leads when you are on a job",
        "preheader": "Every day on a project, your phone goes to voicemail — and leads go elsewhere",
        "body": (
            p("Here's the GC cycle I see constantly: you land a big project, go heads-down for 3 months, and surface with zero pipeline.") +
            p("The calls you missed while you were working? They went to competitors.") +
            p("The homeowners who saw your work and wanted to hire you? They left a voicemail that got buried.") +
            p("The feast-or-famine cycle doesn't have to be your reality. The AI receptionist captures every lead that comes in when you can't answer — books discovery calls, collects project details, schedules callbacks.") +
            p("You come up for air to a full pipeline instead of starting from zero. One call, 20 minutes. I'll show you how it works.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172f0a514be16272535d4": {
        "subject": "Fully booked now — what about 3 months from now?",
        "preheader": "The chair is full today — here is how to keep it full next month too",
        "body": (
            p("Business feels good right now. Book is full, clients are coming in.") +
            p("The question isn't today — it's what happens in 3 months when natural churn hits. The client who went on vacation and hasn't rebooked. The one who tried a new place. The one who just got busy and let it slide.") +
            p("Every shop has a steady drip of clients drifting out the door. The shops that stay consistently full are the ones automatically following up — \"it's been 3 weeks, ready to book?\" — so clients come back before they wander.") +
            p("The system runs in the background. Your work stays exactly the same. One call — let me show you.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b172faa514be8b882536b3": {
        "subject": "Independent shop owners who said business was fine — then the chains ran a promo",
        "preheader": "When Jiffy Lube runs a $19.99 deal — will your regulars stay?",
        "body": (
            p("\"Business is fine — I have regulars.\"") +
            p("Then the chain runs a $19.99 oil change promotion. Your regulars think \"I'll just go this once.\" And because your shop didn't send a reminder that their service was due — they didn't even think of you.") +
            p("You're not losing on quality. You're losing on follow-up. The chain has a $20M CRM doing it automatically.") +
            p("You don't need a $20M CRM. You need AI maintenance reminders at 3, 6, and 12 months. Review requests that build your Google rating. Social content that reminds regulars you exist.") +
            p("One call — I'll show you how independent shops compete.")
        ),
        "cta": "Book My Strategy Call",
    },

    # ── EMAIL 08 ── Day 11 — VIP Urgency Update ─────────────────────────────

    "69b1730b2d6bd6a3b89b99a6": {
        "subject": "Founders VIP update — spots are going",
        "preheader": "A few plumbing founder spots remaining — here is the update",
        "body": (
            p("Quick update on the Founders VIP Program.") +
            p("When I opened founding spots at 75% off, I didn't know how quickly they'd fill. Most are gone.") +
            p("I hold a limited number of spots per trade category — I don't want to take on more clients than I can serve well, and I don't want 10 plumbers in the same market running the same content.") +
            p("There are a small number of <strong>plumbing spots remaining</strong>.") +
            p("The full package: AI-powered website and lead capture, 24/7 AI receptionist, automated review requests, social media content — all at 75% off the monthly rate, only for founding clients.") +
            p("If you've been thinking about it, this is the week to move. Book a call and I'll hold your spot.")
        ),
        "cta": "Hold My Spot",
    },

    "69b17315f99b235f9a0dddcf": {
        "subject": "Founders VIP update — HVAC spots going fast",
        "preheader": "A few HVAC founder spots left — here is the update",
        "body": (
            p("Quick Founders VIP update.") +
            p("HVAC has been the most requested trade category — partly because peak season urgency is real. Business owners in HVAC see immediately why this matters.") +
            p("The <strong>HVAC founding spots are nearly full</strong>. I limit spots per trade so I can deliver real results for each client — not spread thin across too many.") +
            p("The full offer: AI website, 24/7 AI receptionist for peak season overflow, automated maintenance reminders for slow season, social content, reputation management — at 75% off.") +
            p("Book a call this week if you want in.")
        ),
        "cta": "Hold My Spot",
    },

    "69b17320c99bdc57ffaef0ce": {
        "subject": "Founders VIP update — roofing spots filling",
        "preheader": "A few roofing founder spots remaining — quick update",
        "body": (
            p("Quick update. Roofing founding spots are almost full.") +
            p("I want to be direct about why this offer exists: I'm building case studies. Founding clients get 75% off — I get proof the system delivers results for roofing businesses. It's a fair trade.") +
            p("After the first 10 fill their spots, the price returns to standard rate.") +
            p("A small number of <strong>roofing spots remain</strong>. Full package: AI website, 24/7 AI receptionist, automated estimate follow-up, reputation management, social content.") +
            p("This is the week to move. Book a call.")
        ),
        "cta": "Hold My Spot",
    },

    "69b1732a52f4382b2ae29197": {
        "subject": "Founders VIP update — a few spots left",
        "preheader": "Landscaping founder spots almost full — quick update",
        "body": (
            p("Short update. The Founders VIP founding spots for landscaping are nearly full.") +
            p("Spring timing makes this especially relevant. If you want the AI receptionist, social content, and re-booking system running before your peak season — setup needs to start now, not after spring is underway.") +
            p("A few <strong>landscaping spots remain</strong>. Full package: AI website, AI receptionist, reputation management, social content — all at 75% off.") +
            p("Book a call this week and I'll hold your spot through the onboarding.")
        ),
        "cta": "Hold My Spot",
    },

    "69b173339361fc244ecd1a61": {
        "subject": "Founders VIP update — spots going",
        "preheader": "A few cleaning business founder spots remaining",
        "body": (
            p("Quick update on Founders VIP spots.") +
            p("Cleaning businesses have been one of the most active categories — owners in this space understand the retention and reputation problem clearly.") +
            p("The <strong>cleaning business founding spots are almost full</strong>. I don't use artificial pressure — these spots are limited because I limit my client count by design. When they're full, they're full.") +
            p("Full package: AI website, AI receptionist, re-booking automation, reputation management, social content — 75% off for founders.") +
            p("Book a call below.")
        ),
        "cta": "Hold My Spot",
    },

    "69b1733c524c3abe2eaeaaea": {
        "subject": "Founders VIP update — general contracting spots",
        "preheader": "A few GC founder spots remaining — quick update",
        "body": (
            p("Quick update. General contracting founding spots are nearly full.") +
            p("General contracting has the highest average job value of any trade I work with — and also the highest cost of a missed lead. If the AI receptionist captures just one project you would have otherwise lost, it pays for itself for the year.") +
            p("At 75% off founding pricing, the math is simple.") +
            p("A few <strong>GC spots remain</strong>. Book a call this week.")
        ),
        "cta": "Hold My Spot",
    },

    "69b173452d6bd65fed9b9cd6": {
        "subject": "Founders VIP update — shop spots filling",
        "preheader": "A few barber and stylist founder spots remaining",
        "body": (
            p("Quick Founders VIP update. Barber and salon spots are almost full.") +
            p("I want to address something I hear from shop owners: \"I don't know if AI fits my business — it's personal service.\"") +
            p("That's exactly why it works. AI handles the impersonal side — reminders, follow-ups, review requests, re-booking texts — so you can focus entirely on the personal side: the cut, the conversation, the relationship.") +
            p("You stay you. The system handles the business side.") +
            p("75% off for founders. A few <strong>shop spots remain</strong>. Book a call.")
        ),
        "cta": "Hold My Spot",
    },

    "69b1734dc99bdc51c3aef390": {
        "subject": "Founders VIP update — auto shop spots",
        "preheader": "A few auto shop founder spots remaining",
        "body": (
            p("Quick update. Auto shop founding spots are almost full.") +
            p("One number to leave you with: the average independent auto shop loses 30–40% of first-time customers before a second visit — simply because there was no follow-up.") +
            p("AI maintenance reminders at 3 months recover a meaningful portion of those customers. Review requests build the Google rating that makes new customers choose the independent shop over the chain.") +
            p("At 75% off founding pricing — one recovered customer per month more than covers the cost.") +
            p("A few <strong>auto shop spots remain</strong>. Book a call.")
        ),
        "cta": "Hold My Spot",
    },

    # ── EMAIL 09 ── Day 14 — Last Call ──────────────────────────────────────

    "69b173612ba04ab34f9604cb": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — plumbing founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP offer closes when the 10 spots fill — I don't extend it, I don't make exceptions.") +
            p("If you're a plumbing business owner who's been on the fence: the question isn't whether AI will change how service businesses operate — it already is. The question is whether you're in the group of plumbers who gets there first — at 75% off, with a setup built for your business — or whether you watch competitors pull ahead.") +
            p("This is my last ask. If you want a spot, book the call below. If not, I'll keep sending content and we'll stay in touch — no pressure either way.") +
            p("Whatever you decide — thank you for being part of this free content program.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1736a29a494193258ea90": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — HVAC founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP offer closes when the 10 spots fill — I don't extend it.") +
            p("If you're an HVAC business owner who's been thinking about it: peak season is either here or coming. The window to have the AI receptionist running before you're slammed is now, not after.") +
            p("This is my last ask. Book the call if you're in. If not, no pressure — I'll keep sending content and we'll stay in touch.") +
            p("Either way — thank you for being part of this.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b17373980c392385fdb4af": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — roofing founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP offer closes when the 10 spots fill. No extensions.") +
            p("If you're a roofer who's been on the fence: the competitors closing more estimates aren't doing better work — they have better follow-up. Automated estimate follow-up is a core piece of what I'm offering, and at 75% off, the ROI on even one additional closed job is significant.") +
            p("This is my last ask. Book the call if you want a spot. If not — no hard feelings, I'll continue sending content.") +
            p("Thank you for being part of this.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1737b34cbc6408e82a225": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — landscaping founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP founding offer closes when 10 spots fill. No extensions.") +
            p("If you're a landscaping business owner who's been thinking about it: spring re-booking season is either here or weeks away. The window to have re-booking automation running before you need it is now.") +
            p("This is my last ask. Book the call if you're in. If not — no pressure, and thank you for being part of this free content program.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1738452f438fa8de29716": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — cleaning business founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP offer closes when 10 spots fill. No exceptions.") +
            p("If you're a cleaning business owner who's been on the fence: the businesses growing in your space aren't getting more clients — they're keeping the ones they have with re-booking automation and review systems. At 75% off, the math works on retention alone.") +
            p("This is my last ask. Book the call if you want in. If not — no pressure, and thank you for being here.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1738b980c398596fdb676": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — GC founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("Founders VIP closes when 10 spots fill. No extensions.") +
            p("If you're a contractor who's been thinking about it: the feast-or-famine cycle costs more than the program. One captured lead during a busy project more than covers the founding rate for the year.") +
            p("This is my last ask. Book the call if you want in. If not — I'll keep sending content and stay in touch.") +
            p("Thank you for being part of this.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1739329a4946ec858ed97": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — barber and stylist founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("The Founders VIP offer closes when 10 spots fill. I don't extend it.") +
            p("If you're a barber or stylist who's been on the fence: one empty chair per week, 52 weeks — that's real money. The re-booking automation alone can recover that. At 75% off founding pricing, it takes almost nothing to get started.") +
            p("This is my last ask. Book the call if you're in. If not — no hard feelings, and thank you for being part of this.") +
            p("Keep posting that content. It works.")
        ),
        "cta": "Book My Strategy Call",
    },

    "69b1739b23f469d2bcfb0c9e": {
        "subject": "This is the last time I will bring this up",
        "preheader": "Final note — auto shop founding spots close after today",
        "body": (
            p("Last email about this.") +
            p("Founders VIP closes when 10 spots fill. No extensions.") +
            p("If you're an auto shop owner who's been thinking about it: Jiffy Lube spends millions keeping your regulars coming back to them instead of you. At 75% off founding pricing, you can run the same retention playbook for a fraction of what they spend.") +
            p("This is my last ask. Book the call if you want in. If not — no pressure, and thank you for being part of this free content program.") +
            p("Either way — keep using the social content. It'll keep working for you.")
        ),
        "cta": "Book My Strategy Call",
    },
}


def update_template(template_id, subject, preheader, body_html, cta="Book a Free Strategy Call"):
    import subprocess
    html = build_html(subject, preheader, body_html, cta)
    payload = json.dumps({
        "updatedBy": LOCATION_ID,
        "locationId": LOCATION_ID,
        "html": html,
        "previewText": preheader,
    })
    # Use curl to avoid Cloudflare bot detection on Python urllib
    gen = subprocess.run(
        ["python3", "-c", f"import sys; sys.stdout.write({repr(payload)})"],
        capture_output=True, text=True
    )
    result = subprocess.run(
        [
            "curl", "-s", "-X", "PATCH",
            f"https://services.leadconnectorhq.com/emails/builder/{template_id}",
            "-H", f"Authorization: Bearer {API_KEY}",
            "-H", "Version: 2021-07-28",
            "-H", "Content-Type: application/json",
            "-d", "@-",
        ],
        input=payload,
        capture_output=True, text=True, timeout=20
    )
    if result.returncode != 0:
        print(f"  curl error: {result.stderr[:100]}")
        return False
    try:
        data = json.loads(result.stdout)
        if data.get("ok"):
            return True
        print(f"  API error: {result.stdout[:200]}")
        return False
    except Exception as e:
        print(f"  Parse error: {e} — {result.stdout[:100]}")
        return False


def main():
    success = 0
    failed = []
    total = len(TEMPLATES)
    for i, (tid, cfg) in enumerate(TEMPLATES.items(), 1):
        subject = cfg["subject"]
        preheader = cfg.get("preheader", "")
        body_html = cfg["body"]
        cta = cfg.get("cta", "Book a Free Strategy Call")
        print(f"[{i}/{total}] Updating {tid[:8]}... {subject[:50]}", end=" ", flush=True)
        ok = update_template(tid, subject, preheader, body_html, cta)
        if ok:
            success += 1
            print("✓")
        else:
            failed.append(tid)
            print("✗ FAILED")
        time.sleep(0.3)  # gentle rate limiting

    print(f"\nDone: {success}/{total} updated successfully")
    if failed:
        print("Failed IDs:")
        for fid in failed:
            print(f"  {fid}")


if __name__ == "__main__":
    main()
