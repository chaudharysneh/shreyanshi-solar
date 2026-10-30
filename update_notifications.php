<?php

$dir = __DIR__ . '/app/Http/Controllers/Api';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // For Created and Updated which have url()
    $content = preg_replace_callback(
        "/send_admin_notification\('([^']+)',\s*'([^']+)',\s*\[\],\s*url\('[^']+'\s*\.\s*\\$([a-zA-Z0-9_]+)->[a-zA-Z0-9_]+\)\);/",
        function ($matches) {
            $module = $matches[1];
            $action = $matches[2];
            $var = $matches[3];
            
            // Map variable to its name property
            $nameProp = '$' . $var . '->name';
            if ($var === 'meeting' || $var === 'deal') $nameProp = '$' . $var . '->title';
            else if ($var === 'project') $nameProp = '$' . $var . '->project_name';
            else if ($var === 'estimate') $nameProp = '$' . $var . '->estimate_no';
            else if ($var === 'invoice') $nameProp = '$' . $var . '->invoice_no';
            else if ($var === 'freshProduct' || $var === 'bomProduct' || $var === 'product') $nameProp = '$' . $var . '->product_name';
            else if ($var === 'ticket') $nameProp = '$' . $var . '->ticket_name';
            else if ($var === 'followUp') $nameProp = '$' . $var . '->purpose';
            else if ($var === 'customer') $nameProp = '$' . $var . '->name';
            else if ($var === 'lead') $nameProp = '$' . $var . '->name';

            return "send_admin_notification('$module', '$action', $nameProp, []);";
        },
        $content
    );

    // For Deleted which have null
    $content = preg_replace_callback(
        "/send_admin_notification\('([^']+)',\s*'([^']+)',\s*\[\],\s*null\);/",
        function ($matches) {
            $module = $matches[1];
            $action = $matches[2];
            
            $nameProp = 'N/A';
            if ($module === 'Deal') $nameProp = '$dealName ?? \'N/A\'';
            else if ($module === 'Support Ticket') $nameProp = '$ticketName ?? \'N/A\'';
            else if ($module === 'BOM Product') $nameProp = '$productName ?? \'N/A\'';
            else if ($module === 'Estimate') $nameProp = '$estimateName ?? \'N/A\'';
            else if ($module === 'Invoice') $nameProp = '$invoiceName ?? \'N/A\'';
            else if ($module === 'Project') $nameProp = '$projectName ?? \'N/A\'';
            else if ($module === 'Lead') $nameProp = '$leadName ?? \'N/A\'';
            else if ($module === 'Customer') $nameProp = '$customerName ?? \'N/A\'';
            else if ($module === 'Meeting') $nameProp = '$meetingName ?? \'N/A\'';
            else if ($module === 'Follow-Up') $nameProp = '$followUpName ?? \'N/A\'';

            return "send_admin_notification('$module', '$action', $nameProp, []);";
        },
        $content
    );
    
    // Status history is different
    $content = preg_replace(
        "/send_admin_notification\('([^']+)',\s*'([^']+)',\s*\[(.*?)\]\);/s",
        "send_admin_notification('$1', '$2', 'N/A', [$3]);",
        $content
    );

    file_put_contents($file, $content);
}
echo "Done";
