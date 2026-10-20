(function () {
    "use strict";

    // ─── Configuration ────────────────────────────────────────────
    const script = document.currentScript;
    const token = script?.getAttribute("data-token");
    const endpoint =
        script?.getAttribute("data-endpoint") ||
        script?.src.replace("tracker.js", "api/track");

    if (!token) {
        console.warn("[Flux] Missing data-token on the script tag.");
        return;
    }

    // ─── Respect Do Not Track ─────────────────────────────────────
    if (navigator.doNotTrack === "1" || window.doNotTrack === "1") {
        console.warn("[Flux] Tracking disabled because Do Not Track is enabled.");
        return;
    }

    // ─── Ignore known bots ────────────────────────────────────────
    const botsPattern = /bot|crawl|spider|slurp|teoma|archive|track/i;
    if (botsPattern.test(navigator.userAgent)) {
        return;
    }

    // ─── Device detection ─────────────────────────────────────────
    function detectDevice() {
        const userAgent = navigator.userAgent;
        if (/tablet|ipad|playbook|silk/i.test(userAgent)) return "tablet";
        if (
            /mobile|iphone|ipod|android|blackberry|mini|windows\sce|palm/i.test(
                userAgent,
            )
        )
            return "mobile";
        return "desktop";
    }

    // ─── Referrer domain extraction ───────────────────────────────
    function extractReferrerDomain(referrerUrl) {
        if (!referrerUrl) return null;
        try {
            return new URL(referrerUrl).hostname;
        } catch {
            return null;
        }
    }

    // ─── UTM parameter extraction ─────────────────────────────────
    function extractUtm() {
        const params = new URLSearchParams(window.location.search);
        return {
            utm_source: params.get("utm_source") || null,
            utm_medium: params.get("utm_medium") || null,
            utm_campaign: params.get("utm_campaign") || null,
        };
    }

    // ─── Session duration ─────────────────────────────────────────
    const startedAt = Date.now();

    // ─── Payload delivery ─────────────────────────────────────────
    function send(payload) {
        const body = JSON.stringify(payload);

        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: "application/json" });
                navigator.sendBeacon(endpoint, blob);
            } else {
                fetch(endpoint, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body,
                    keepalive: true,
                });
            }
        } catch (error) {
            console.error("[Flux] Unable to send tracking payload.", error);
        }
    }

    // ─── Main pageview collection ─────────────────────────────────
    function trackPageview(isNavigation) {
        const utm = extractUtm();

        send({
            token,
            url: window.location.href,
            path: window.location.pathname,
            title: document.title || null,
            referrer: isNavigation ? null : document.referrer || null,
            referrer_domain: isNavigation
                ? null
                : extractReferrerDomain(document.referrer),
            device: detectDevice(),
            screen_width: window.innerWidth,
            is_navigation: isNavigation || false,
            ...utm,
        });
    }

    // ─── Duration reporting ───────────────────────────────────────
    function sendDuration() {
        const duration = Math.round((Date.now() - startedAt) / 1000);

        if (duration < 2) return;

        send({
            token,
            type: "duration",
            path: window.location.pathname,
            duration,
        });
    }

    // ─── Initialization ───────────────────────────────────────────
    trackPageview(false);

    window.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "hidden") {
            sendDuration();
        }
    });

    window.addEventListener("beforeunload", sendDuration);

    // Public API for custom events: Flux.event('signup', { plan: 'pro' })
    window.Flux = {
        event: function (name, data) {
            send({
                token,
                type: "event",
                name,
                path: window.location.pathname,
                data: data || {},
            });
        },
    };

    // ─── Plugin injection point ───────────────────────────────────
    /* PLUGIN_INJECTION */
})();
