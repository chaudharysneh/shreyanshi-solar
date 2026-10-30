<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
    <tr>
        <td width="50%" align="left" valign="top">
            <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
            </div>
        </td>
        <td width="50%" align="right" valign="top">
            <?php if (!empty($logoBase64)): ?>
            <div style="display: inline-block; text-align: right;">
                <img src="<?= $logoBase64 ?>" alt="Company Logo"
                    style="max-width: 250px; max-height: 100px; object-fit: contain; height: auto; margin-bottom: 5px;">
            </div>
            <?php endif; ?>
        </td>
    </tr>
</table>
