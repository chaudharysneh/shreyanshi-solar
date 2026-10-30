<?php
$galleryImgStyle = static function (int $height): string {
    return 'width:100%;height:' . $height . 'px;object-fit:cover;object-position:center;display:block;';
};

$galleryCommentSection = is_array($estimateCommentSection ?? null) ? $estimateCommentSection : [];
$galleryCommentText = '';

if (isset($estdata) && $estdata) {
    $estimateComment = trim((string) ($estdata->comment ?? ''));
    if ($estimateComment === '' && isset($estdata->estimate_comment)) {
        $estimateComment = trim((string) $estdata->estimate_comment);
    }
    if ($estimateComment !== '' && $estimateComment !== '--') {
        $galleryCommentText = $estimateComment;
    }
}

if ($galleryCommentText === '' && (int) ($galleryCommentSection['active'] ?? 0) === 1) {
    $galleryCommentText = trim((string) ($galleryCommentSection['content'] ?? ''));
}

$galleryCommentHtml = '';
if ($galleryCommentText !== '') {
    $plainComment = $galleryCommentText;
    if (preg_match('/<[^>]+>/', $plainComment)) {
        $plainComment = preg_replace('/<br\s*\/?>/i', "\n", $plainComment);
        $plainComment = preg_replace('/<\/p>/i', "\n", $plainComment);
        $plainComment = preg_replace('/<\/div>/i', "\n", $plainComment);
        $plainComment = strip_tags($plainComment);
        $plainComment = html_entity_decode($plainComment, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $plainComment = trim(preg_replace("/\n{3,}/", "\n\n", $plainComment));
    if ($plainComment !== '') {
        $galleryCommentHtml = nl2br(e($plainComment));
    }
}
?>
<div style="page-break-inside: avoid;">
<table width="100%" cellpadding="0" cellspacing="0" style="margin: 4px 0 8px; border-collapse: collapse;">
    <tr>
        <td width="50%" valign="top" style="padding-right: <?= (int) ($galleryGap / 2) ?>px;">
            <div style="width:100%;height:<?= (int) $galleryLeftHeight ?>px;overflow:hidden;background:#eef4ee;">
                <img src="<?= $img1 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryLeftHeight) ?>">
            </div>
        </td>
        <td width="50%" valign="top" style="padding-left: <?= (int) ($galleryGap / 2) ?>px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td valign="top" style="padding-bottom: <?= (int) ($galleryGap / 2) ?>px;">
                        <div style="width:100%;height:<?= (int) $galleryRightHeight ?>px;overflow:hidden;background:#eef4ee;">
                            <img src="<?= $img2 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryRightHeight) ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-top: <?= (int) ($galleryGap / 2) ?>px;">
                        <div style="width:100%;height:<?= (int) $galleryRightHeight ?>px;overflow:hidden;background:#eef4ee;">
                            <img src="<?= $img3 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryRightHeight) ?>">
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 18px; margin-bottom: 6px;">
    <tr>
        <td align="center">
            <div style="font-size: 13px; text-align: center; font-family: 'Montserrat', sans-serif; color: #444;">
                Each site is installed end to end with 5 years of AMC & monitoring
            </div>
        </td>
    </tr>
</table>

<?php if ($galleryCommentHtml !== ''): ?>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 8px; margin-bottom: 4px;">
    <tr>
        <td align="center" style="padding: 0 4px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 0; background-color: #eef8ee; border: 2px dotted #7cb87a; border-radius: 8px;">
                <tr>
                    <td align="center" valign="middle" style="padding: 12px 16px; font-size: 13px; text-align: center; font-family: 'Montserrat', sans-serif; color: #444; line-height: 1.5;">
                        <?= $galleryCommentHtml ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php endif; ?>
</div>
