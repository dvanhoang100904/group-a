<?php
// File: public/test-zip.php
// Test ZipArchive functionality

header('Content-Type: application/json');

$results = [
    'php_version' => PHP_VERSION,
    'zip_extension' => extension_loaded('zip'),
    'ziparchive_class' => class_exists('ZipArchive'),
];

// Test 1: Check paths
$basePath = dirname(__DIR__);
$storagePath = $basePath . '/storage/app/temp';
$testZipPath = $storagePath . '/test_' . uniqid() . '.zip';

$results['paths'] = [
    'base_path' => $basePath,
    'storage_path' => $storagePath,
    'test_zip_path' => $testZipPath,
    'temp_exists' => file_exists($storagePath),
    'temp_writable' => is_writable($storagePath),
];

// Test 2: Try to create directory
if (!file_exists($storagePath)) {
    $created = mkdir($storagePath, 0777, true);
    $results['directory_creation'] = [
        'attempted' => true,
        'success' => $created,
    ];
}

// Test 3: Try to create ZIP
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $openResult = $zip->open($testZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $results['zip_creation'] = [
        'open_result' => $openResult,
        'open_result_text' => $openResult === true ? 'SUCCESS' : getZipErrorMessage($openResult),
    ];

    if ($openResult === true) {
        // Add test file
        $zip->addFromString('test.txt', 'This is a test file at ' . date('Y-m-d H:i:s'));
        $zip->close();

        // Check if file was created
        $fileExists = file_exists($testZipPath);
        $fileSize = $fileExists ? filesize($testZipPath) : 0;

        $results['zip_test'] = [
            'file_exists' => $fileExists,
            'file_size' => $fileSize,
            'file_path' => $testZipPath,
        ];

        // Try to read the ZIP
        if ($fileExists) {
            $readZip = new ZipArchive();
            $readResult = $readZip->open($testZipPath);

            if ($readResult === true) {
                $results['zip_read'] = [
                    'num_files' => $readZip->numFiles,
                    'file_0_name' => $readZip->getNameIndex(0),
                ];
                $readZip->close();
            }

            // Clean up
            unlink($testZipPath);
            $results['cleanup'] = 'Test file deleted';
        }
    }
} else {
    $results['error'] = 'ZipArchive class not available';
}

// Test 4: Check write permissions in detail
$testFile = $storagePath . '/test_write_' . uniqid() . '.txt';
$writeTest = @file_put_contents($testFile, 'test');
$results['write_test'] = [
    'attempted' => true,
    'success' => $writeTest !== false,
    'bytes_written' => $writeTest,
    'file_path' => $testFile,
];

if ($writeTest !== false) {
    unlink($testFile);
}

// Test 5: Check PHP configuration
$results['php_config'] = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'open_basedir' => ini_get('open_basedir') ?: 'none',
];

echo json_encode($results, JSON_PRETTY_PRINT);

function getZipErrorMessage($code)
{
    $errors = [
        ZipArchive::ER_EXISTS => 'ER_EXISTS: File already exists',
        ZipArchive::ER_INCONS => 'ER_INCONS: Zip archive inconsistent',
        ZipArchive::ER_INVAL => 'ER_INVAL: Invalid argument',
        ZipArchive::ER_MEMORY => 'ER_MEMORY: Malloc failure',
        ZipArchive::ER_NOENT => 'ER_NOENT: No such file',
        ZipArchive::ER_NOZIP => 'ER_NOZIP: Not a zip archive',
        ZipArchive::ER_OPEN => 'ER_OPEN: Cannot open file',
        ZipArchive::ER_READ => 'ER_READ: Read error',
        ZipArchive::ER_SEEK => 'ER_SEEK: Seek error',
    ];

    return $errors[$code] ?? "Unknown error code: $code";
}
