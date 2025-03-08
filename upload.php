<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Directory where the uploaded files will be stored
    $targetDir = "uploads/";
    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    // Create uploads directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Allowed file types
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $fileType = mime_content_type($_FILES["fileToUpload"]["tmp_name"]);

    // Check if the file type is allowed
    if (in_array($fileType, $allowedTypes)) {
        // Check if the file was uploaded without errors
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFilePath)) {
            echo "The file " . htmlspecialchars($fileName) . " has been uploaded.<br>";
            echo "Original File Size: " . round(filesize($targetFilePath) / 1024, 2) . " KB<br>";

            // Compress image files
            if ($fileType == 'image/jpeg') {
                compressJPEG($targetFilePath);
                echo "The JPEG file has been compressed for web display.<br>";
                echo "<img src='" . htmlspecialchars($targetFilePath) . "' alt='Compressed JPEG' style='max-width: 300px;'><br>";
                echo "Compressed File Size: " . round(filesize($targetFilePath) / 1024, 2) . " KB<br>";
            } elseif ($fileType == 'image/png') {
                compressPNG($targetFilePath);
                echo "The PNG file has been compressed for web display.<br>";
                echo "<img src='" . htmlspecialchars($targetFilePath) . "' alt='Compressed PNG' style='max-width: 300px;'><br>";
                echo "Compressed File Size: " . round(filesize($targetFilePath) / 1024, 2) . " KB<br>";
            } elseif ($fileType == 'application/pdf') {
                $compressedFilePath = compressPDF($targetFilePath);
                echo "The PDF file has been compressed.<br>";
                echo "Download Compressed PDF: <a href='" . htmlspecialchars($compressedFilePath) . "'>Download</a><br>";
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "Sorry, only JPG, PNG, and PDF files are allowed.";
    }
} else {
    echo "Invalid request.";
}

// Function to compress JPEG images
function compressJPEG($filePath) {
    $image = imagecreatefromjpeg($filePath);
    imagejpeg($image, $filePath, 0); // 75 is the quality (0-100)
    imagedestroy($image);
}

// Function to compress PNG images
function compressPNG($filePath) {
    $image = imagecreatefrompng($filePath);
    imagepng($image, $filePath, 9); // 0 (no compression) to 9 (maximum compression)
    imagedestroy($image);
}

// Function to compress PDF files using Ghostscript
function compressPDF($filePath) {
    $outputFilePath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '_compressed.pdf';
    
    // Path to the Ghostscript executable
    $ghostscriptPath = __DIR__ . '/ghostscript/bin/gswin64c.exe'; // Adjust if using 32-bit version

    // Ghostscript command to compress PDF
    $command = escapeshellcmd($ghostscriptPath) . " -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($outputFilePath) . " " . escapeshellarg($filePath);
    
    // Execute the command
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        return $outputFilePath; // Return the path to the compressed file
    } else {
        echo "Failed to compress PDF.";
        return null;
    }
}
?>