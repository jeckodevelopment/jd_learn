# JD Learn 1.0.0 — Browser storage, cookies and CMP administrator guide

**Status:** Source-code inventory and operating guide, 20 November 2026. Live browser scans, CMP/provider integration checks and privacy approval are **pending**. This is not a site-specific cookie notice, legal classification or certification.

## First-party browser storage inventory

| Key | Component | Storage and value | Purpose | Duration | Consent classification |
| --- | --- | --- | --- | --- | --- |
| `jdlearn.video.speed.v1` | `component/media/js/video.js` | Origin-scoped `localStorage`; allowed numerical playback rate | Remember a user's playback-speed choice | No application expiry; until browser/site removal | Determine with the site's privacy owner; do not assume exemption |
| `jdlearn.video.language.v1` | `component/media/js/video.js`; read by YouTube and Cloudflare Stream adapters | Origin-scoped `localStorage`; validated language tag (e.g. `it-IT`) | Reuse preferred captions/subtitle language when supported | No application expiry; until browser/site removal or option reset | Determine with the site's privacy owner; do not assume exemption |

JD Learn stores course progress, enrollment and email consent on the **server**, not in these two keys. The video player tolerates storage disabled or inaccessible. Avoid storing lesson answers, login data and secrets in local storage. The static inventory gate `tests/privacy-storage-inventory.py` checks direct localStorage API calls in distributed JD Learn component and video-adapter JavaScript against the two documented keys; it does not scan external SDKs, inline third-party scripts or dynamically created storage names.

Joomla session/authentication cookies and cookies or storage created by YouTube, Vimeo and Cloudflare Stream depend on the actual site, provider, geographic region, browser and embed settings. Record them **separately** during browser testing. `youtube-nocookie.com` and Vimeo `dnt=1` do not prove there are no tracking/storage operations.

## Configure the CMP bridge

1. Choose an external video policy appropriate to the site: `blocked` or `site_consent` when prior consent is required. Do not select unrestricted embedding as a workaround.
2. Integrate the site's CMP with `window.JlmsConsent.isAllowed(providerKey)` and emit `jlms:consent-changed` whenever permissions change. The bridge must map each provider explicitly; JD Learn cannot automatically configure every CMP.
3. Before consent, verify that no optional external SDK, iframe or provider request is loaded. After an affirmative permission, verify playback. On revocation, verify that existing players are destroyed and new launches are prevented. Earlier network requests cannot be undone.
4. Validate the actual site's CSP, device/browser behavior, provider privacy settings and the current site-specific cookie notice. Do not treat newsletter/email opt-in as consent for external video.
5. Keep a dated audit record: site/build, Joomla version, browser, region, provider, CMP mode and cookie/storage name/domain/path, value type, attributes, duration, purpose, responsible party, behavior before/after consent, revocation and removal method. Do **not** record real session tokens, account identifiers or personal values.

## Mandatory pre-release verification

- Start from a fresh guest browser profile, then repeat signed in, on catalog, course, classroom and administration pages. Check Network, Cookies, Local Storage, Session Storage, IndexedDB and caches against a Joomla-only baseline.
- Test local HTML5, YouTube, Vimeo and Cloudflare Stream separately, including a deny/allow/revoke consent cycle and disabled localStorage. Repeat in Italian and English on desktop/mobile.
- Confirm that the two first-party keys contain only a permitted playback rate and language tag, respectively; browser storage access failure must not block lessons.
- Check the third-party SDK's observed behavior under the site's CMP/CSP configuration. Record results, browser, date and build in the QA testbook. The source inventory does **not** substitute for these tests.
- Have the site privacy owner or adviser assess categorization, legal basis, retention and published notices. Review after Joomla, JD Learn, CMP or video SDK updates.

The Italian source guide is [PRIVACY_COOKIES.md](PRIVACY_COOKIES.md). The current release gate remains **open** for real-world consent checks and browser scans.
