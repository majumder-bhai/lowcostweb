const THEME_STORAGE_KEY = "playBonusHub.theme.v1";
const ITEMS_PER_PAGE = 100;
const APPS_CACHE_KEY = "yonoapps.cache.v2";
const APPS_CACHE_MAX_AGE_MS = 10 * 60 * 1000;

const pageType = document.body?.dataset?.page || "home";
const appGrid = document.getElementById("appGrid");
const sectionTabsEl = document.getElementById("sectionTabs");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageInfo = document.getElementById("pageInfo");
const pagerEl = document.getElementById("pager");
const themeToggle = document.getElementById("themeToggle");
const shareNowBtn = document.getElementById("shareNowBtn");
const appsUpdatedAtEl = document.getElementById("appsUpdatedAt");
const detailsRoot = document.getElementById("detailsRoot");
const relatedAppsEl = document.getElementById("relatedApps");

const APP_SECTIONS = ["All Apps", "New Apps", "Best Apps"];
let apps = [];
let selectedCategory = "All Apps";
let currentPage = 1;

function normalizeCategory(value) {
  const text = String(value || "").trim().toLowerCase();
  if (text === "new apps") {
    return "New Apps";
  }
  if (text === "best apps") {
    return "Best Apps";
  }
  return "All Apps";
}

function slugify(value) {
  return String(value || "")
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[^\w\s-]/g, "")
    .trim()
    .replace(/[\s_-]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function normalizeLogoUrl(value) {
  const raw = String(value || "").trim();
  if (!raw) {
    return "";
  }
  if (/^https?:\/\//i.test(raw) || raw.startsWith("/") || raw.startsWith("data:")) {
    return raw;
  }
  return `/${raw.replace(/^\.?\//, "")}`;
}

function parseRupeeAmount(text) {
  const match = String(text || "").match(/(\d+(?:\.\d+)?)/);
  return match ? Number(match[1]) : null;
}

function buildAppOverviewLines(app) {
  const ratingValue = Number.isFinite(app.rating) ? Math.max(0, Math.min(5, Number(app.rating))) : 0;
  const bonusAmount = parseRupeeAmount(app.bonus);
  const withdrawAmount = parseRupeeAmount(app.withdraw);
  const categoryLabel = normalizeCategory(app.category);

  const lineOne = `${app.name} is listed in ${categoryLabel} with a user rating of ${ratingValue.toFixed(1)}/5.`;
  const lineTwo = bonusAmount
    ? `Current offer shows approximately Rs ${bonusAmount} sign-up bonus for new users.`
    : "Current offer includes a sign-up bonus for new users.";
  const lineThree = withdrawAmount
    ? `Minimum withdraw starts around Rs ${withdrawAmount}. Please verify latest terms inside the app before making any deposit.`
    : "Please verify latest withdraw terms inside the app before making any deposit.";

  return [lineOne, lineTwo, lineThree];
}

function setTheme(theme) {
  const safeTheme = theme === "light" ? "light" : "dark";
  document.body.setAttribute("data-theme", safeTheme);
  localStorage.setItem(THEME_STORAGE_KEY, safeTheme);
  themeToggle.textContent = safeTheme === "dark" ? "Light" : "Dark";
}

function initTheme() {
  const storedTheme = localStorage.getItem(THEME_STORAGE_KEY);
  setTheme(storedTheme || "dark");
}

function renderSectionTabs() {
  if (!sectionTabsEl) {
    return;
  }
  sectionTabsEl.innerHTML = "";
  APP_SECTIONS.forEach((section) => {
    const button = document.createElement("button");
    button.className = `section-tab ${selectedCategory === section ? "active" : ""}`;
    button.textContent = section;
    button.type = "button";
    button.addEventListener("click", () => {
      selectedCategory = section;
      currentPage = 1;
      renderSectionTabs();
      renderApps();
    });
    sectionTabsEl.appendChild(button);
  });
}

function getVisibleApps() {
  return apps.filter((app) => {
    const appCategory = normalizeCategory(app.category);
    return selectedCategory === "All Apps" || appCategory === selectedCategory;
  });
}

function formatUpdatedLabel() {
  const now = new Date();
  const text = now.toLocaleDateString(undefined, {
    day: "2-digit",
    month: "short",
    year: "numeric"
  });
  if (appsUpdatedAtEl) {
    appsUpdatedAtEl.textContent = `Updated: ${text}`;
  }
}

function renderApps() {
  if (!appGrid || !pageInfo || !prevBtn || !nextBtn) {
    return;
  }
  const visibleApps = getVisibleApps();
  const totalPages = Math.max(1, Math.ceil(visibleApps.length / ITEMS_PER_PAGE));
  if (currentPage > totalPages) {
    currentPage = totalPages;
  }

  const start = (currentPage - 1) * ITEMS_PER_PAGE;
  const pageItems = visibleApps.slice(start, start + ITEMS_PER_PAGE);

  if (!pageItems.length) {
    appGrid.innerHTML = "<p>No apps found for this section.</p>";
  } else {
    appGrid.innerHTML = pageItems
      .map(
        (app, index) => `
      <article class="app-row">
        <p class="app-index">${start + index + 1}.</p>
        <img
          class="app-row-logo"
          src="${app.logo}"
          alt="${app.name} logo"
          loading="${start + index < 6 ? "eager" : "lazy"}"
          fetchpriority="${start + index < 3 ? "high" : "auto"}"
          decoding="async"
        />
        <div class="app-main">
          ${
            start + index < 3
              ? `<span class="app-badge hot">${
                  start + index === 0 ? "HOT" : start + index === 1 ? "TRENDING" : "POPULAR"
                }</span>`
              : ""
          }
          <h3 class="app-title">${app.name}</h3>
          <p class="bonus-line">🎁 ${app.bonus}</p>
          <p class="withdraw-line">🏦 ${app.withdraw}</p>
        </div>
        <div class="app-side">
          <a class="button app-download" href="/app/${encodeURIComponent(app.slug)}">Download</a>
        </div>
      </article>
    `
      )
      .join("");
  }

  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
  prevBtn.disabled = currentPage <= 1;
  nextBtn.disabled = currentPage >= totalPages;
  if (pagerEl) {
    pagerEl.classList.toggle("hidden", totalPages <= 1);
  }
}

async function loadApps() {
  const response = await fetch("/apps.json", { cache: "no-store" });
  if (!response.ok) {
    throw new Error("Could not load app list.");
  }

  const data = await response.json();
  if (!Array.isArray(data)) {
    throw new Error("Invalid apps data format.");
  }

  return data;
}

function setAppsFromData(data) {
  const baseApps = data.map((item) => ({
    id: Number(item.id || 0),
    name: String(item.name || "").trim(),
    category: String(item.category || "All Apps").trim(),
    bonus: String(item.bonus || "").trim(),
    withdraw: String(item.withdraw || "").trim(),
    rating: Number(item.rating || 0),
    url: String(item.url || "#").trim(),
    logo: normalizeLogoUrl(item.logo)
  })).map((item, idx) => ({
    ...item,
    id: item.id > 0 ? item.id : idx + 1
  }));

  const usedSlugs = new Map();
  apps = baseApps.map((item, idx) => {
    const base = slugify(item.name) || `app-${item.id || idx + 1}`;
    const seen = usedSlugs.get(base) || 0;
    usedSlugs.set(base, seen + 1);
    const slug = seen === 0 ? base : `${base}-${seen + 1}`;
    return { ...item, slug };
  });
}

function readAppsCache() {
  try {
    const raw = localStorage.getItem(APPS_CACHE_KEY);
    if (!raw) {
      return null;
    }
    const parsed = JSON.parse(raw);
    if (!parsed || !Array.isArray(parsed.data) || typeof parsed.ts !== "number") {
      return null;
    }
    if (Date.now() - parsed.ts > APPS_CACHE_MAX_AGE_MS) {
      return null;
    }
    return parsed.data;
  } catch {
    return null;
  }
}

function writeAppsCache(data) {
  try {
    localStorage.setItem(APPS_CACHE_KEY, JSON.stringify({ ts: Date.now(), data }));
  } catch {
    // no-op
  }
}

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = document.body.getAttribute("data-theme") || "dark";
    setTheme(currentTheme === "dark" ? "light" : "dark");
  });
}

if (prevBtn) {
  prevBtn.addEventListener("click", () => {
    currentPage -= 1;
    renderApps();
  });
}

if (nextBtn) {
  nextBtn.addEventListener("click", () => {
    currentPage += 1;
    renderApps();
  });
}

if (shareNowBtn) {
  shareNowBtn.addEventListener("click", async () => {
    const shareUrl = window.location.origin;
    const shareText = "Check out YONO APPS ALL";

    if (navigator.share) {
      try {
        await navigator.share({
          title: "YONO APPS ALL",
          text: shareText,
          url: shareUrl
        });
        return;
      } catch {
        // fallthrough
      }
    }

    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(`${shareText} ${shareUrl}`)}`;
    window.open(whatsappUrl, "_blank", "noopener");
  });
}

(async function init() {
  initTheme();
  if (pageType === "home") {
    renderSectionTabs();
    if (appGrid) {
      appGrid.innerHTML = Array.from({ length: 6 })
        .map(() => '<article class="skeleton-row" aria-hidden="true"></article>')
        .join("");
    }
  }

  const cachedApps = readAppsCache();
  if (cachedApps) {
    setAppsFromData(cachedApps);
    if (pageType === "details") {
      renderDetailsPage();
    } else {
      renderApps();
      formatUpdatedLabel();
    }
  }

  try {
    const freshData = await loadApps();
    setAppsFromData(freshData);
    writeAppsCache(freshData);
    if (pageType === "details") {
      renderDetailsPage();
      return;
    }
    renderApps();
    formatUpdatedLabel();
  } catch (error) {
    if (pageType === "details" && detailsRoot) {
      detailsRoot.innerHTML = `<p>${error.message || "Could not load app details."}</p>`;
      return;
    }
    if (appGrid) {
      appGrid.innerHTML = `<p>${error.message || "Could not load apps."}</p>`;
    }
    if (pageInfo) {
      pageInfo.textContent = "Page 1 of 1";
    }
    if (prevBtn) {
      prevBtn.disabled = true;
    }
    if (nextBtn) {
      nextBtn.disabled = true;
    }
  }
})();

function renderDetailsPage() {
  if (!detailsRoot) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  let appSlug = String(params.get("app") || "").trim().toLowerCase();
  if (!appSlug) {
    const pathMatch = window.location.pathname.match(/^\/app\/([^/?#]+)/i);
    if (pathMatch && pathMatch[1]) {
      appSlug = decodeURIComponent(pathMatch[1]).trim().toLowerCase();
    }
  }
  const rawId = params.get("id") || "";
  const id = parseInt(rawId, 10);
  const queryName = String(params.get("name") || "").trim().toLowerCase();
  let app = null;

  if (appSlug) {
    app = apps.find((item) => String(item.slug || "").toLowerCase() === appSlug) || null;
  }
  if (!app && Number.isFinite(id) && id > 0) {
    app = apps.find((item) => item.id === id) || null;
  }
  if (!app && queryName) {
    app = apps.find((item) => String(item.name || "").trim().toLowerCase() === queryName) || null;
  }

  if (!app) {
    detailsRoot.innerHTML = `
      <article class="details-card">
        <h1>App not found</h1>
        <p>This app link is invalid or removed.</p>
        <a class="button app-download" href="/">Go Back Home</a>
      </article>
    `;
    return;
  }

  const ratingValue = Number.isFinite(app.rating) ? Math.max(0, Math.min(5, Number(app.rating))) : 0;
  const safeRating = ratingValue.toFixed(1);
  const ratingFillPercent = (ratingValue / 5) * 100;

  document.title = `${app.name} - YONO APPS ALL`;
  const canonicalEl = document.querySelector('link[rel="canonical"]');
  if (canonicalEl) {
    canonicalEl.setAttribute("href", `${window.location.origin}/app/${encodeURIComponent(app.slug)}`);
  }
  const descEl = document.querySelector('meta[name="description"]');
  if (descEl) {
    descEl.setAttribute(
      "content",
      `${app.name}: ${app.bonus}. ${app.withdraw}. Check details and download from YONO APPS ALL.`
    );
  }
  detailsRoot.innerHTML = `
    <article class="details-card">
      <div class="details-top">
        <img class="details-logo" src="${app.logo}" alt="${app.name} logo" loading="eager" decoding="async" />
        <div class="details-main">
          <p class="details-kicker">App Details</p>
          <h1 class="details-title">${app.name}</h1>
          <p class="details-subtitle">Verified listing from YONO APPS ALL</p>
        </div>
      </div>

      <div class="details-stats">
        <div class="details-stat details-rating-stat">
          <div class="details-rating-stars" aria-label="Rating ${safeRating} out of 5">
            <span class="stars-base">★★★★★</span>
            <span class="stars-fill" style="width:${ratingFillPercent}%;">★★★★★</span>
          </div>
          <strong class="rating-number">${safeRating}/5</strong>
          <small>Rating</small>
        </div>
        <div class="details-stat"><span>🎁</span> ${app.bonus}<small>Sign-up Bonus</small></div>
        <div class="details-stat"><span>🏦</span> ${app.withdraw}<small>Withdraw</small></div>
      </div>

      <div class="details-actions">
        <a class="button app-download details-download" href="${app.url}" target="_blank" rel="noopener nofollow">DOWNLOAD ${app.name}</a>
        <a class="button telegram-join-btn details-telegram-join" href="https://t.me/+XhcIwcI3Y8w5MjNl" target="_blank" rel="noopener">Join Our Telegram Channel</a>
        <a class="button ghost details-back" href="/">Back to Home</a>
      </div>

      <section class="details-copy">
        <h2> ${app.name}</h2>
        ${buildAppOverviewLines(app).map((line) => `<p>${line}</p>`).join("")}
      </section>
    </article>
  `;

  injectDetailsStructuredData(app, safeRating);
  renderRelatedApps(app);
}

function injectDetailsStructuredData(app, safeRating) {
  const oldEl = document.getElementById("detailsSchema");
  if (oldEl) {
    oldEl.remove();
  }
  const schema = {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    name: app.name,
    operatingSystem: "Android",
    applicationCategory: "GameApplication",
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: safeRating,
      bestRating: "5",
      ratingCount: "100"
    },
    offers: {
      "@type": "Offer",
      price: "0",
      priceCurrency: "INR",
      url: app.url
    }
  };
  const script = document.createElement("script");
  script.id = "detailsSchema";
  script.type = "application/ld+json";
  script.textContent = JSON.stringify(schema);
  document.head.appendChild(script);
}

function renderRelatedApps(currentApp) {
  if (!relatedAppsEl) {
    return;
  }

  const sameCategory = apps.filter((app) => app.id !== currentApp.id && normalizeCategory(app.category) === normalizeCategory(currentApp.category));
  const fallback = apps.filter((app) => app.id !== currentApp.id);
  const related = (sameCategory.length ? sameCategory : fallback).slice(0, 8);

  if (!related.length) {
    relatedAppsEl.innerHTML = "<p>No related apps found.</p>";
    return;
  }

  relatedAppsEl.innerHTML = related
    .map(
      (app, idx) => `
      <article class="app-row related-row">
        <p class="app-index">${idx + 1}.</p>
        <img class="app-row-logo" src="${app.logo}" alt="${app.name} logo" loading="lazy" decoding="async" />
        <div class="app-main">
          <h3 class="app-title">${app.name}</h3>
          <p class="bonus-line">🎁 ${app.bonus}</p>
          <p class="withdraw-line">🏦 ${app.withdraw}</p>
        </div>
        <div class="app-side">
          <a class="button app-download" href="/app/${encodeURIComponent(app.slug)}">Details</a>
        </div>
      </article>
    `
    )
    .join("");
}
