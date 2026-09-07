<fieldset class="grid" style="gap:10px;border:1px solid var(--border);padding:14px;border-radius:8px">
  <legend>Egna inställningar för kortet</legend>
  <p class="muted">Välj ”Följ originalet” för att ärva värdet. Egna värden påverkar bara detta referenskort. Mognad styr färgen i den nya vyn; den klassiska vyn använder kartans valda färgfält.</p>
  <?php
  $referenceOptions = [
    'layer' => ['Skikt / placering', cfg('taxonomy')['layers'] ?? []],
    'maturity' => ['Mognad / färg', [0 => 'Ej bedömd', 1 => '1 · Initial', 2 => '2 · Under utveckling', 3 => '3 · Definierad', 4 => '4 · Hanterad', 5 => '5 · Optimerad']],
    'criticality' => ['Kritikalitet', [0, 1, 2, 3, 4, 5]],
    'risk_level' => ['Risknivå', [0, 1, 2, 3, 4, 5]],
    'level' => ['Förmågenivå', array_combine(cfg('taxonomy')['levels'] ?? [1,2,3], cfg('taxonomy')['levels'] ?? [1,2,3])],
  ];
  foreach ($referenceOptions as $key => [$label, $options]): ?>
    <label><?= h($label) ?><select class="select" name="reference_options[<?= h($key) ?>]">
      <option value="">Följ originalet</option>
      <?php foreach ($options as $value => $text): ?><option value="<?= h((string)$value) ?>" <?= isset($referenceValues[$key]) && (string)$referenceValues[$key] === (string)$value ? 'selected' : '' ?>><?= h((string)$text) ?></option><?php endforeach; ?>
    </select></label>
  <?php endforeach; ?>
</fieldset>
