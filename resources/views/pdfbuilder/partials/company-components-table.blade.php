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
            <th width="8%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:center;">S.N.</th>
            <th width="52%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:left;">BOM</th>
            <th width="40%" style="padding:11px 10px;border:0;border-top:1px solid #0a2540;border-bottom:1px solid #0a2540;color:#0a2540;font-size:9px;letter-spacing:0.7px;text-align:left;">MAKE / BRAND</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $componentSerial = 0;
    foreach ($componentsTableRows ?? $componentsData as $component):
        $componentSerial++;
        $plain = fn ($value) => html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $make = trim($plain($component['category'] ?? ''));
        if ($make === '' && is_array($component['specifications'] ?? null)) {
            foreach ($component['specifications'] as $spec) {
                if (is_array($spec) && strtolower(trim($plain($spec[0] ?? ''))) === 'make') {
                    $make = trim($plain($spec[1] ?? ''));
                    if ($make !== '') break;
                }
            }
        }
    ?>
    <tr style="page-break-inside:avoid;background:#fff;">
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#24435f;font-size:10px;vertical-align:middle;text-align:center;"><?= $componentSerial ?></td>
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#0a2540;font-size:11px;font-weight:bold;line-height:1.4;vertical-align:middle;word-wrap:break-word;">
            <?= esc($component['name'] ?? '--') ?>
        </td>
        <td style="padding:12px 10px;border:0;border-bottom:1px solid #dce5ef;color:#24435f;font-size:10px;line-height:1.5;vertical-align:middle;word-wrap:break-word;">
            <?= $make !== '' ? esc($make) : '&mdash;' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
