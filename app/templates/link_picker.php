<dialog id="capability-link-dialog" style="width:min(620px,92vw);max-height:85vh;overflow:auto;border:1px solid var(--border);border-radius:12px;background:var(--surface,Canvas);color:var(--text,CanvasText);padding:22px">
  <h2 style="margin-top:0">Infoga länk till förmåga</h2>
  <p class="muted">Sök i alla kartor. Länken använder kartans nyckel och förmågans ID.</p>
  <label for="capability-link-search">Sök namn, ID eller karta</label>
  <input class="input" id="capability-link-search" type="search" autocomplete="off">
  <p id="capability-link-status" role="status" aria-live="polite"></p>
  <label for="capability-link-target">Förmåga</label>
  <select class="select" id="capability-link-target" size="8" style="width:100%"></select>
  <label for="capability-link-label">Länktext</label>
  <input class="input" id="capability-link-label">
  <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
    <button class="btn btn--ghost" id="capability-link-cancel" type="button">Avbryt</button>
    <button class="btn btn--primary" id="capability-link-insert" type="button" disabled>Infoga länk</button>
  </div>
</dialog>
<script defer src="<?= h(base_path('assets/capability-link-picker.js')) ?>"></script>
