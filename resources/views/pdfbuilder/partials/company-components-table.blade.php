<div>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:10px 0 18px;border:0;font-family:'DejaVu Sans',sans-serif;page-break-inside:auto;table-layout:fixed;">
    <thead>
        <tr>
            <td colspan="3" style="padding:40px 0 18px;border:0;background:#fff;text-align:left;">
                <?php if (isset($quantity)): ?>
                @include('pdfbuilder.partials.pdf-page-header')
                <?php endif; ?>
                <div style="font-size:32px;font-weight:bold;line-height:1.15;font-family:'Montserrat',sans-serif;color:#000;border-left:8px solid #4b9349;padding-left:18px;">BOM (Bill of Materials)</div>
            </td>
        </tr>
        <tr>
            <th width="30%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:center;">COMPONENT NAME</th>
            <th width="30%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:left;">BRAND &amp; CATEGORY</th>
            <th width="40%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:left;">TECHNICAL SPECIFICATIONS</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($componentsTableRows ?? $componentsData as $component):
        $plain = fn ($value) => html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $make = trim($plain($component['category'] ?? ''));
        $productImagePath = null;
        foreach (['image', 'image_path', 'product_image', 'photo'] as $imageKey) {
            if (empty($component[$imageKey])) continue;
            $resolved = normalize_pdf_image(trim((string) $component[$imageKey]));
            if ($resolved && str_starts_with($resolved, 'data:image/')) { $productImagePath = $resolved; break; }
        }
        $type = '';
        $tags = [];
        $qty = trim((string) ($component['quantity'] ?? ''));
        if ($qty !== '') $tags[] = 'Qty: ' . $qty;
        $specs = $component['specifications'] ?? [];
        if (is_array($specs)) {
            foreach ($specs as $spec) {
                if (!is_array($spec) || count($spec) < 2) continue;
                $key = trim($plain($spec[0] ?? ''));
                $value = trim($plain($spec[1] ?? ''));
                if ($key === '' || $value === '') continue;
                if (strtolower($key) === 'make') { if ($make === '') $make = $value; }
                elseif (strtolower($key) === 'type') $type = $value;
                else $tags[] = $key . ': ' . $value;
            }
        } elseif (trim($plain($specs)) !== '') $type = $plain($specs);
        if (!empty($component['description'])) $tags[] = 'Desc: ' . $plain($component['description']);
        $sku = trim((string) ($component['sku'] ?? ''));
    ?>
    <tr style="page-break-inside:avoid;background:#fff;">
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#0a2540;vertical-align:middle;text-align:center;">
            <?php if ($productImagePath): ?>
            <div style="text-align:center;margin-bottom:7px;"><img src="<?= esc($productImagePath) ?>" alt="<?= esc($component['name'] ?? 'Component') ?>" style="max-width:76px;max-height:66px;width:auto;height:auto;"></div>
            <?php endif; ?>
            <div style="font-size:11px;font-weight:bold;line-height:1.4;text-align:center;word-wrap:break-word;"><?= esc($component['name'] ?? '--') ?></div>
            <?php if ($sku !== ''): ?><div style="font-size:8px;color:#8796a8;margin-top:3px;">SKU: <?= esc($sku) ?></div><?php endif; ?>
        </td>
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#24435f;font-size:10px;line-height:1.5;vertical-align:middle;word-wrap:break-word;">
            <?= $make !== '' ? esc($make) : '&mdash;' ?>
        </td>
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#24435f;font-size:10px;line-height:1.6;vertical-align:middle;word-wrap:break-word;">
            <?php if ($type !== ''): ?><div style="margin-bottom:5px;"><span style="color:#0a2540;">Type:</span> <?= esc($type) ?></div><?php endif; ?>
            <?php foreach ($tags as $tag): ?><span style="display:inline-block;background:#f0f4f8;color:#4a627b;font-size:8px;line-height:1.5;padding:3px 5px;margin:0 4px 4px 0;border-radius:3px;"><?= esc($tag) ?></span><?php endforeach; ?>
            <?php if ($type === '' && !$tags): ?>&mdash;<?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
