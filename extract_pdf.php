<?php
require 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('resources/views/pdfbuilder/QT-000150.pdf');
$output = '';
foreach ($pdf->getPages() as $i => $page) {
    $output .= "\n--- PAGE " . ($i+1) . " ---\n" . $page->getText();
}
file_put_contents('pdf_extract.txt', $output);
echo "Extracted text to pdf_extract.txt\n";
