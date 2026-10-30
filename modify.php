<?php
$file = "d:/xampp/htdocs/CRM/rising-green-crm/resources/views/pdfbuilder/qt-000150-pdf.blade.php";
$content = file_get_contents($file);

$func = "
if (!function_exists(\"numberToWords\")) {
    function numberToWords(\$num) {
        \$num = (int) \$num;
        \$words = [];
        \$list1 = [\"\", \"One\", \"Two\", \"Three\", \"Four\", \"Five\", \"Six\", \"Seven\", \"Eight\", \"Nine\", \"Ten\", \"Eleven\", \"Twelve\", \"Thirteen\", \"Fourteen\", \"Fifteen\", \"Sixteen\", \"Seventeen\", \"Eighteen\", \"Nineteen\"];
        \$list2 = [\"\", \"Ten\", \"Twenty\", \"Thirty\", \"Forty\", \"Fifty\", \"Sixty\", \"Seventy\", \"Eighty\", \"Ninety\"];
        if (\$num == 0) return \"Zero\";
        \$crores = floor(\$num / 10000000); \$num -= \$crores * 10000000;
        \$lakhs = floor(\$num / 100000); \$num -= \$lakhs * 100000;
        \$thousands = floor(\$num / 1000); \$num -= \$thousands * 1000;
        \$hundreds = floor(\$num / 100); \$num -= \$hundreds * 100;
        \$tens = floor(\$num / 10); \$ones = \$num % 10;
        if (\$crores > 0) \$words[] = numberToWords(\$crores) . \" Crore\";
        if (\$lakhs > 0) \$words[] = numberToWords(\$lakhs) . \" Lakh\";
        if (\$thousands > 0) \$words[] = numberToWords(\$thousands) . \" Thousand\";
        if (\$hundreds > 0) \$words[] = numberToWords(\$hundreds) . \" Hundred\";
        if (\$tens > 0 || \$ones > 0) {
            if (\$tens < 2) \$words[] = \$list1[\$tens * 10 + \$ones];
            else { \$words[] = \$list2[\$tens]; if (\$ones > 0) \$words[] = \$list1[\$ones]; }
        }
        return implode(\" \", \$words);
    }
}
";

$content = str_replace("<?php\nif (!function_exists", "<?php\n" . $func . "\nif (!function_exists", $content);

$quoteHtml = <<<EOT
    <section class="page">
        <div style="width: 100%; margin-bottom: 20px; display: table;">
            <div style="display: table-cell; width: 60%; vertical-align: top;">
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">{{ \$companyName }}</div>
                <div style="font-size: 11px; line-height: 1.3;">
                    PM SURYAGHAR EMP NO. NPDG-164<br>
                    316 SUNTRADE CENTER RAMNAGAR Surat Gujarat 395005<br>
                    India<br>
                    Surat Gujarat 395005<br>
                    India<br>
                    GSTIN 24ABBFR1974L1ZY<br>
                    {{ \$companyPhone }}<br>
                    {{ \$companyEmail }}
                </div>
            </div>
            <div style="display: table-cell; width: 40%; vertical-align: top; text-align: right;">
                <div style="font-size: 32px; font-weight: normal; margin-bottom: 5px; color: #000;">Quote</div>
                <div style="font-size: 14px; font-weight: bold; color: #000;"># {{ \$doc->estimate_no ?? \"QT-000150\" }}</div>
            </div>
        </div>

        <div style="width: 100%; margin-bottom: 20px; display: table;">
            <div style="display: table-cell; width: 50%; vertical-align: top;">
                <div style="font-size: 12px; color: #555; margin-bottom: 3px;">Bill To</div>
                <div style="font-size: 13px; font-weight: bold;">{{ \$clientName }}</div>
            </div>
            <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;">
                <div style="font-size: 12px;">Quote Date : &nbsp;&nbsp;&nbsp;&nbsp; {{ \$doc->estimate_date ? \Carbon\Carbon::parse(\$doc->estimate_date)->format(\"d/m/Y\") : date(\"d/m/Y\") }}</div>
            </div>
        </div>
        
        <div style="font-size: 12px; margin-bottom: 20px;">
            Place Of Supply: Gujarat (24)
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 10px;">
            <thead>
                <tr style="background-color: #333; color: #fff;">
                    <th style="padding: 8px 5px; text-align: left; font-weight: normal; border: 1px solid #333;">#</th>
                    <th style="padding: 8px 5px; text-align: left; font-weight: normal; border: 1px solid #333;">Item &amp; Description</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">HSN/SAC</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">Qty</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">Rate</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">CGST</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">SGST</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: normal; border: 1px solid #333;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px 5px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top;">1</td>
                    <td style="padding: 10px 5px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top;">
                        SUPPLY AND INSTALLATION<br>
                        <span style="color:#555; font-size:10px;">{{ \$capacity }} SOLAR ROOFTOP SYSTEM</span>
                    </td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">854140</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$capacityValue > 0 ? \$plainNumber(\$capacityValue, 2) : \"3.30\" }}</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$baseSystemValue, 2) }}</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$gstValue/2, 2) }}<br><span style="font-size:9px;color:#555;">{{ \$gstRate/2 }}%</span></td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$gstValue/2, 2) }}<br><span style="font-size:9px;color:#555;">{{ \$gstRate/2 }}%</span></td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$baseSystemValue, 2) }}</td>
                </tr>
                @if(\$solarStructureValue > 0)
                <tr>
                    <td style="padding: 10px 5px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top;">2</td>
                    <td style="padding: 10px 5px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top;">
                        STRUCTURE FABRICATION WORK<br>
                        <span style="color:#555; font-size:10px;">{{ \$capacity }} SOLAR ROOFTOP SYSTEM</span>
                    </td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">998873</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$capacityValue > 0 ? \$plainNumber(\$capacityValue, 2) : \"3.30\" }}</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$solarStructureValue, 2) }}</td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$solarStructureValue * 0.09, 2) }}<br><span style="font-size:9px;color:#555;">9%</span></td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$solarStructureValue * 0.09, 2) }}<br><span style="font-size:9px;color:#555;">9%</span></td>
                    <td style="padding: 10px 5px; text-align: right; border-bottom: 1px solid #ddd; vertical-align: top;">{{ \$plainNumber(\$solarStructureValue, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div style="width: 100%; display: table;">
            <div style="display: table-cell; width: 50%;"></div>
            <div style="display: table-cell; width: 50%;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <tr>
                        <td style="padding: 5px; text-align: right; width: 70%;">Sub Total</td>
                        <td style="padding: 5px; text-align: right; width: 30%;">{{ \$plainNumber(\$baseSystemValue + \$solarStructureValue, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">CGST{{ \$gstRate/2 }} ({{ \$gstRate/2 }}%)</td>
                        <td style="padding: 5px; text-align: right;">{{ \$plainNumber(\$gstValue/2, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">SGST{{ \$gstRate/2 }} ({{ \$gstRate/2 }}%)</td>
                        <td style="padding: 5px; text-align: right;">{{ \$plainNumber(\$gstValue/2, 2) }}</td>
                    </tr>
                    @if(\$solarStructureValue > 0)
                    <tr>
                        <td style="padding: 5px; text-align: right;">CGST9 (9%)</td>
                        <td style="padding: 5px; text-align: right;">{{ \$plainNumber(\$solarStructureValue * 0.09, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; text-align: right;">SGST9 (9%)</td>
                        <td style="padding: 5px; text-align: right;">{{ \$plainNumber(\$solarStructureValue * 0.09, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 10px 5px; text-align: right; background-color: #f2f2f2; font-weight: bold; font-size: 13px;">Total</td>
                        <td style="padding: 10px 5px; text-align: right; background-color: #f2f2f2; font-weight: bold; font-size: 13px;">&#8377;{{ \$plainNumber(\$grossValue, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="font-size: 12px; margin-top: 15px;">
            Total In Words: <strong>Indian Rupee {{ numberToWords(\$grossValue) }} Only</strong>
        </div>

        <div style="margin-top: 30px; font-size: 11px;">
            <div style="font-size: 14px; color: #333; margin-bottom: 10px;">Notes</div>
            ALL BILL OF MATERIAL USED FOR INSTALLATION ARE AS PER PM SURYAGHAR GUIDLINE GOVERMENT CRITERIA AND AS PER ISI STANDER WITH AUTHENTIC CERTIFICATES<br><br>
            DOCUMENTS LIST:<br>
            ELECTRICITY BILL<br>
            VERA BILL<br>
            ADHARCARD<br>
            PANCARD<br>
            CANCELCHEQUE<br>
            CONCENT LETTER (FOR COMMON TERRACE)
        </div>

        <div style="margin-top: 30px; font-size: 11px;">
            <div style="font-size: 14px; color: #333; margin-bottom: 10px;">Terms &amp; Conditions</div>
            - ESTIMATE VALID FOR 10DAYS ONLY<br>
            - GEDA REGISTRATION FEES EXTRA AS PER ACTUAL (IF APPLICABLE)<br>
            - METER CHARGES EXTRA AS PER ACTUAL DGVCL/TORRENT (IF APPLICABLE)<br>
            - SUBSIDY IN CLIENT ACCOUNT {{ \$plainNumber(\$subsidyValue, 0) }}/- <br>
            - AVG MONTHLY SAVING {{ \$monthlyGeneration }} UNITS GENERATION WHICH SAVES UP TO {{ \$plainNumber(\$monthlyGeneration * 8, 0) }}/-
        </div>
    </section>

    <section class="page">
        <div style="margin-top: 50px; font-size: 12px;">
            Authorized Signature _____________________________
        </div>
    </section>
EOT;

$content = str_replace("<body>", "<body>\n" . \$quoteHtml, $content);
file_put_contents(\$file, \$content);
?>
