<?php if (isset($cap->meta['redirect_map']) && is_authed()): ?>
<a href="<?= h(base_path('editor/reference.php?map=' . rawurlencode($selectedKey) . '&id=' . rawurlencode($cap->id))) ?>" style="display:block;margin-top:12px;padding-top:8px;border-top:1px solid #94a3b850;font-size:12px;text-decoration:underline" aria-label="<?= h('Kortinställningar för ' . $cap->name) ?>">⚙ Kortinställningar</a>
<?php endif; ?>
