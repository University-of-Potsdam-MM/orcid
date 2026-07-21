(async function () {

  function bibtexToCslJsonItemWithCitationJs(bibtex) {
    if (typeof window.Cite !== "function") {
      throw new Error("citation-js not loaded (window.Cite missing)");
    }

    // Parse BibTeX
    const cite = new window.Cite(bibtex);

    // CSL-JSON items as JS objects
    const data = cite.format("data", { format: "object" }); // returns array of CSL items
    if (!Array.isArray(data) || data.length === 0) {
      throw new Error("citation-js produced no CSL-JSON items");
    }
    return data[0];
  }

  function mergePreferExisting(existing, fromBibtex) {
    const merged = { ...existing, ...fromBibtex };
    merged.id = existing.id;
    if (existing.DOI) merged.DOI = existing.DOI;
    if (existing.URL) merged.URL = existing.URL;
    return merged;
  }

  function enrichItemsWithBibtex(items) {
    return items.map((it) => {
      const bibtex =
        it.__orcidCitationType === "bibtex"
          ? (it.__orcidCitationValue || "").trim()
          : "";
      if (!bibtex) return it;

      try {
        const fromBib = bibtexToCslJsonItemWithCitationJs(bibtex);
        return mergePreferExisting(it, fromBib);
      } catch (e) {
        console.warn("BibTeX→CSL (citation-js) failed for", it.id, e);
        return it;
      }
    });
  }

  function hasAnyItems(groups) {
    if (!Array.isArray(groups) || groups.length === 0) return false;
    return groups.some((g) => Array.isArray(g.items) && g.items.length > 0);
  }

  function showEmptyMessage(container) {
    const emptyEl = container.querySelector(".orcid-citations__empty");
    if (emptyEl) {
      emptyEl.hidden = false;
      container.innerHTML = "";
      container.appendChild(emptyEl);
      return;
    }
    const text = container.dataset.emptyMessage || "ORCID Datensatz nicht gefunden.";
    container.innerHTML = `<p class="orcid-citations__empty">${escapeHtml(text)}</p>`;
  }

  function assertCsl() {
    if (!window.CSL || !window.CSL.Engine) {
      throw new Error("citeproc.js not loaded (window.CSL.Engine missing)");
    }
  }

  async function fetchText(url) {
    const res = await fetch(url, { credentials: "same-origin" });
    if (!res.ok) throw new Error(`Failed to fetch ${url}: HTTP ${res.status}`);
    return await res.text();
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function buildSys(itemsById, localeXml) {
    return {
      retrieveLocale: () => localeXml,
      retrieveItem: (id) => itemsById[id],
    };
  }

  function createFilter(keyword, itemList) {
    const filter = {};
    filter[keyword] = itemList;
    return filter;
  }
  function createFilterItem(field, value) {
    return { field, value };
  }

  /**
   * Rewrite numeric labels in rendered bibliography entries so they match base index.
   * This only affects styles that actually print a numeric label.
   */
  function rewriteEntryLabelToIndex(html, index) {
    // common numeric prefixes: [1] ... OR (1) ... OR 1. ... OR 1) ...
    let out = html;
    out = out.replace(/^\s*\[(\d+)\]/, `[${index}]`);
    out = out.replace(/^\s*\((\d+)\)/, `(${index})`);
    out = out.replace(/^\s*(\d+)\.\s+/, `${index}. `);
    out = out.replace(/^\s*(\d+)\)\s+/, `${index}) `);
    return out;
  }

  function readPayloadForContainer(container) {
    const id = container.id;
    if (!id) throw new Error("Missing container id");

    const uid = id.replace(/^orcid-citations-/, "");
    const jsonNode = document.getElementById("orcid-payload-" + uid);
    if (!jsonNode) throw new Error("Missing payload script tag for uid " + uid);

    const jsonText = (jsonNode.textContent || "").trim();
    if (!jsonText) throw new Error("Empty JSON payload for uid " + uid);

    return JSON.parse(jsonText);
  }

  async function renderOne(container) {
    assertCsl();

    const payload = readPayloadForContainer(container);

    const styleName = payload.styleName || "apa";
    const locale = payload.locale || "en-GB";
    const styleBaseUrl = (payload.styleBaseUrl || "").replace(/\/$/, "");
    const localeBaseUrl = (payload.localeBaseUrl || "").replace(/\/$/, "");
    const groups = Array.isArray(payload.groups) ? payload.groups : [];

    if (!hasAnyItems(groups)) {
      showEmptyMessage(container);
      return;
    }

    if (!styleBaseUrl || !localeBaseUrl) {
      throw new Error("Missing styleBaseUrl/localeBaseUrl in payload");
    }

    const styleUrl = `${styleBaseUrl}/${encodeURIComponent(styleName)}.csl`;
    const localeUrl = `${localeBaseUrl}/locales-${encodeURIComponent(locale)}.xml`;

    const [styleXml, localeXml] = await Promise.all([
      fetchText(styleUrl),
      fetchText(localeUrl),
    ]);

    // Build global item store + id->baseIndex map from payload order
    const itemsById = {};
    const idToIndex = {};

    for (const g of groups) {
      const itemsRaw = Array.isArray(g.items) ? g.items : [];
      for (const entry of itemsRaw) {
        const idx = entry && typeof entry === "object" && "index" in entry ? entry.index : null;
        const it = entry && typeof entry === "object" && "item" in entry ? entry.item : entry;

        if (!it || typeof it !== "object") continue;
        const id = String(it.id);

        // enrich bibtex per item (one-by-one to keep payload order and mapping stable)
        const [enriched] = enrichItemsWithBibtex([it]);

        itemsById[id] = enriched;
        if (idx !== null && idx !== undefined) {
          idToIndex[id] = idx;
        }
      }
    }

    const sys = buildSys(itemsById, localeXml);
    const engine = new CSL.Engine(sys, styleXml);
    engine.opt.development_extensions.wrap_url_and_doi = true;

    // Preload engine with all IDs once (helps some styles / engine state)
    engine.updateItems(Object.keys(itemsById));

    // Render per group with filters, but using SAME engine instance
    const htmlParts = [];

    for (const g of groups) {
      const label = g.label || "";
      const itemsRaw = Array.isArray(g.items) ? g.items : [];

      const idsInGroup = [];
      for (const entry of itemsRaw) {
        const it = entry && typeof entry === "object" && "item" in entry ? entry.item : entry;
        if (!it || typeof it !== "object") continue;
        idsInGroup.push(String(it.id));
      }

      const groupParts = ['<div class="orcid-citations__wrap">'];

      if (label) {
        groupParts.push(
          `<h3 class="orcid-citations__group">${escapeHtml(label)}</h3>`
        );
      }

      if (idsInGroup.length === 0) {
        groupParts.push("</div>");
        htmlParts.push(groupParts.join("\n"));
        continue;
      }

      // Filter by ID (field "id" is supported by citeproc.js filters)
      const filterList = idsInGroup.map((id) => createFilterItem("id", id));
      const filter = createFilter("include", filterList);

      const bib = engine.makeBibliography(filter);
      let entries = bib && bib[1] ? bib[1] : [];

      // Rewrite displayed label to base index (if style uses numeric labels)
      entries = entries.map((html, i) => {
        const id = idsInGroup[i];
        const baseIndex = idToIndex[id];

        if (baseIndex === undefined) return html; // if not provided, leave as-is
        return rewriteEntryLabelToIndex(html, baseIndex);
      });

      groupParts.push(entries.join("\n"));
      groupParts.push("</div>");
      htmlParts.push(groupParts.join("\n"));
    }

    container.innerHTML = htmlParts.join("\n");
  }

  const nodes = document.querySelectorAll('.orcid-citations[id^="orcid-citations-"]');
  for (const node of nodes) {
    try {
      await renderOne(node);
    } catch (e) {
      node.innerHTML = `<p class="orcid-citations__error">Citation rendering failed.</p>`;
      console.error(e);
    }
  }
})();
