(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  ready(function () {
    const api = window.BRLGPD || null;
    if (!api) return;

    const qs = (sel, root) => (root || document).querySelector(sel);

    function getOverlay() { return qs("#brlgpd-modal-overlay"); }
    function getModal() { return qs("#brlgpd-modal"); }
    function getBanner() { return qs("#brlgpd-banner"); }

    function normalizeKey(k) {
      return (k || "")
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9_-]/g, "")
        .slice(0, 32);
    }

    function openModal() {
      const overlay = getOverlay();
      if (!overlay) return;
      overlay.classList.remove("brlgpd-hidden");
      overlay.setAttribute("aria-hidden", "false");
    }

    function closeModal() {
      const overlay = getOverlay();
      if (!overlay) return;
      overlay.classList.add("brlgpd-hidden");
      overlay.setAttribute("aria-hidden", "true");
    }

    function hideBanner() {
      const banner = getBanner();
      if (!banner) return;
      banner.classList.add("brlgpd-hidden");
    }

    function setBusy(isBusy) {
      document.querySelectorAll("[data-brlgpd-action]").forEach((b) => {
        b.disabled = !!isBusy;
      });
    }

    function collectChoicesFromModal() {
      const modal = getModal();
      if (!modal) return {};

      const choices = {};
      modal.querySelectorAll("input[type=checkbox][data-brlgpd-cat]").forEach((el) => {
        const raw = el.getAttribute("data-brlgpd-cat") || "";
        const key = normalizeKey(raw);
        if (!key) return;
        choices[key] = !!el.checked;
      });

      return choices;
    }

    function allOptionalKeys() {
      // 1) chaves vindas do PHP (pode ficar desatualizado se o HTML/JS foi cacheado)
      const apiKeys = Array.isArray(api.optional_keys) ? api.optional_keys : [];

      // 2) fallback: lê do DOM (sempre reflete as categorias realmente renderizadas no modal)
      const domKeys = [];
      const modal = getModal();
      if (modal) {
        modal.querySelectorAll("input[type=checkbox][data-brlgpd-cat]").forEach((el) => {
          if (el.disabled) return; // ignora always_active
          const raw = el.getAttribute("data-brlgpd-cat") || "";
          const key = normalizeKey(raw);
          if (key) domKeys.push(key);
        });
      }

      const merged = apiKeys
        .filter((k) => typeof k === "string" && k.length)
        .map((k) => normalizeKey(k))
        .concat(domKeys)
        .filter((k) => k.length);

      // unique
      return Array.from(new Set(merged));
    }

    function applyChoicesToModal(choices) {
      const modal = getModal();
      if (!modal) return;

      const map = (choices && typeof choices === "object") ? choices : {};
      modal.querySelectorAll("input[type=checkbox][data-brlgpd-cat]").forEach((el) => {
        // não mexe nos disabled (always_active)
        if (el.disabled) return;

        const raw = el.getAttribute("data-brlgpd-cat") || "";
        const key = normalizeKey(raw);
        if (!key) return;

        el.checked = !!map[key];
      });
    }

    async function postAjax(action, extraFields) {
      setBusy(true);
      try {
        const form = new URLSearchParams();
        form.set("action", action);
        form.set("nonce", api.nonce);

        if (extraFields) {
          Object.keys(extraFields).forEach((k) => form.set(k, extraFields[k]));
        }

        const res = await fetch(api.ajax_url, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          body: form.toString(),
        });

        const json = await res.json();
        return json;
      } catch (e) {
        console.warn("BRLGPD: erro", e);
        return null;
      } finally {
        setBusy(false);
      }
    }

    async function refreshStateAndUI() {
      const json = await postAjax("brlgpd_get_consent", null);
      if (!json || !json.success || !json.data) return;

      const data = json.data;

      // Mantém a lista de categorias opcionais sincronizada (importante quando o HTML/JS vem de cache)
      if (data.optional_keys && Array.isArray(data.optional_keys)) {
        api.optional_keys = data.optional_keys;
      }

      // Atualiza checkboxes do modal
      applyChoicesToModal(data.choices || {});

      // Se já tem consent e policy ok, some o banner (mesmo se HTML veio cacheado)
      if (data.has_consent && !data.should_renew) {
        hideBanner();
      }
    }

    async function postConsent(payload) {
      const json = await postAjax("brlgpd_save_consent", {
        choices: JSON.stringify(payload.choices || {}),
      });

      return json;
    }

    async function acceptAll() {
      const choices = {};
      allOptionalKeys().forEach((k) => (choices[k] = true));

      const json = await postConsent({ choices });
      if (json && json.success) {
        // aplica estado devolvido pelo servidor (sem depender do HTML)
        if (json.data && json.data.choices) applyChoicesToModal(json.data.choices);

        closeModal();
        hideBanner();

        // Se quiser manter o reload, pode. Mas com cache, não confie nele.
        // window.location.reload();
      }
    }

    async function rejectAll() {
      const choices = {};
      allOptionalKeys().forEach((k) => (choices[k] = false));

      const json = await postConsent({ choices });
      if (json && json.success) {
        if (json.data && json.data.choices) applyChoicesToModal(json.data.choices);

        closeModal();
        hideBanner();
        // window.location.reload();
      }
    }

    async function savePrefs() {
      const choices = collectChoicesFromModal();

      const json = await postConsent({ choices });
      if (json && json.success) {
        if (json.data && json.data.choices) applyChoicesToModal(json.data.choices);

        closeModal();
        hideBanner();
        // window.location.reload();
      }
    }

    document.addEventListener("click", async (e) => {
      const actionEl = e.target.closest("[data-brlgpd-action]");
      const openEl = e.target.closest("[data-brlgpd-open]");

      if (openEl) {
        e.preventDefault();
        openModal();
        // ✅ sempre sincroniza ao abrir (garante MIDIA/novas categorias marcadas certo)
        await refreshStateAndUI();
        return;
      }

      if (!actionEl) return;

      e.preventDefault();
      const act = actionEl.getAttribute("data-brlgpd-action");

      if (act === "open") {
        openModal();
        await refreshStateAndUI();
      }
      if (act === "close") closeModal();
      if (act === "accept") acceptAll();
      if (act === "reject") rejectAll();
      if (act === "save") savePrefs();
    });

    document.addEventListener("click", (e) => {
      const overlay = getOverlay();
      if (!overlay) return;
      if (overlay.classList.contains("brlgpd-hidden")) return;
      if (e.target === overlay) closeModal();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeModal();
    });

    // ✅ na carga da página, corrige estado mesmo com cache
    refreshStateAndUI();
  });
})();
