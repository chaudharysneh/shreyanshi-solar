<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px; border-collapse: collapse;">
    <tr>
        <td style="padding: 20px 24px 18px 22px;">
            <div class="pdf-rich-content pdf-rich-content-spacious pdf-company-page-about">
                <?php if ($companyDescription !== ''): ?>
                    <?= $companyDescription ?>
                <?php elseif (($estdata->type ?? '') === 'residential'): ?>
                    <p style="margin-bottom: 14px; font-size: 16px; font-weight: normal;">Dear <strong><?= esc($preparedForName) ?></strong>,</p>
                    <p style="margin-bottom: 14px; font-size: 16px; text-align: justify; line-height: 1.65; font-weight: normal;">
                        Thank you for giving <strong><?= esc($globalCompanyName) ?></strong> the opportunity to present this customized solar energy proposal<?= !empty($hasClientAddress) ? ' for your property located at <strong>' . esc($clientAddress) . '</strong>' : '' ?>.
                    </p>
                    <p style="margin-bottom: 14px; font-size: 16px; text-align: justify; line-height: 1.65; font-weight: normal;">
                        With electricity tariffs rising consistently year after year, switching to solar is no longer just an environmental choice—it is one of the smartest and safest financial investments available today. At <strong><?= esc($globalCompanyName) ?></strong>, we combine premium Tier-1 components, precise engineering, and seamless multi-stage execution to ensure your transition to clean energy is entirely effortless and highly profitable.
                    </p>
                    <p style="margin-bottom: 14px; font-size: 16px; text-align: justify; line-height: 1.65; font-weight: normal;">
                        Enclosed, you will find a comprehensive breakdown of your custom tailored solar solution, expected annual generation metrics, long-term financial returns, and an end-to-end implementation roadmap.
                    </p>
                    <p style="margin-bottom: 0; font-size: 16px; line-height: 1.65; font-weight: normal;">
                        Best Regards,<br><br>
                        <strong><?= e($profileUser->name ?? 'Rising Green Energy Team') ?></strong><br>
                        <?= esc($globalCompanyName) ?>
                    </p>
                <?php else: ?>
                    <?= $companyDescription !== '' ? $companyDescription : '<p>We are on a mission to deliver 10,000 world-class solar installations ensuring maximum performance, durability, and ROI for every project.</p>' ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>
