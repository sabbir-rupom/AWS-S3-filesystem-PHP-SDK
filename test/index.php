<?php
/**
 * A simple PHP helper class to manipulate files in AWS S3 buckets using aws-sdk-php library.
 *
 * @author Sabbir Hossain Rupom <sabbir.hossain.rupom@hotmail.com>
 */
require_once 'AwsS3Helper.php';

// The following test script covers 80% functionalities of the helper class as example
// The script does the following works
// Create a new unique bucket if not exist in S3
// Create a directory inside the newly created bucket
// Select and upload multiple files inside the newly created directory in S3 bucket
// List all the files existing in the newly created directory
// Show live links of the uploaded files in the list
// Delete any of the above uploaded files from S3 bucket
// Delete the directory along with all existing folder inside from S3 bucket

/**
 *  Get object of AwsS3Helper instance.
 */
$awsSdkObj = AwsS3Helper::getInstance();

/**
 * Create a temporary folder for intermediate uploaded file storage before AWS-S3 file transfer.
 */
$tempUploadPath = createDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'temp-aws');

$files = [];
$bucketName = $dirName = $delDir = '';

// File upload process to AWS S3
if (isset($_POST['upload'])) {
    $bucketName = str_replace(' ', '-', trim($_POST['bucketName']));

    if ('' != $bucketName) {
        /**
         * Create a new bucket in S3.
         */
        $result = $awsSdkObj->createBucket($bucketName, true);

        // Sow error message if failed to create bucket
        if (!$result['success']) {
            die($result['msg']);
        }
    }

    if (empty($_POST['dirName'])) {
        die('Directory name is empty!');
    }

    $dirName = str_replace(' ', '-', trim($_POST['dirName']));

    $total = count($_FILES['image']['name']); // Count # of uploaded files in array
    // check if any error occurred during file upload
    if (count(array_filter($_FILES['image']['error'])) > 0) {
        foreach ($_FILES['image']['error'] as $error) {
            echo uploadErrorMessage($error) . '<br><br>';
        }
        die('Upload error!');
    }

    // Loop through each uploaded file
    for ($i = 0; $i < $total; ++$i) {
        $files[] = $fileName = $_FILES['image']['name'][$i];
        $fileKey = $dirName . '/' . $fileName;

        $tmpFilePath = $_FILES['image']['tmp_name'][$i]; // Get the temp file path

        if ('' != $tmpFilePath) { // Make sure file path is not empty
            $fileSource = $tempUploadPath . DIRECTORY_SEPARATOR . $fileName; // Setup upload file path in temporary folder
            // Step 1: Upload files to a temporary directory/folder
            if (move_uploaded_file($tmpFilePath, $fileSource)) {
                /**
                 * Step 2: Transfer uploaded files to AWS S3 bucket.
                 */
                $uploadResutl = $awsSdkObj->uploadFileToS3($bucketName, $fileKey, $fileSource, AwsS3Helper::getMimeType($fileName));

                // Check file upload result
                if (empty($uploadResutl)) {
                    die('Failed to upload file in S3 Storage!');
                }
                if (false == $uploadResutl['success']) {
                    die('Failed to upload file in S3 Storage! ' . $uploadResutl['msg']);
                }
            } else {
                die('Unknown error occure during file upload!');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>AWS S3 Multiple file upload & Directory Delete</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>

        <!-- A sample input form to create new bucket, directory and upload new files in S3 -->
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" method="POST" >
            New Bucket: <input type="text" name="bucketName" required> <br><br>
            Folder Name: <input type="text" name="dirName" required> <br><br>
            Select images: <input type="file" name="image[]" multiple required><br><br>
            <input type="submit" name="upload" value="Upload">
        </form><br><br>

<?php
if (isset($_GET['delete'])) {
    /**
     * Delete directory files based on request condition.
     */
    $deleteStatus = intval($_GET['delete']);
    $bucketName = urldecode($_GET['bucket']);
    $dirName = urldecode($_GET['directory']);

    if (1 == $deleteStatus) {
        /**
         * Delete requested file from S3.
         */
        $deleteFileKey = urldecode($_GET['file_key']);
        $result = $awsSdkObj->deleteFileFromS3($bucketName, $deleteFileKey);
    } elseif (2 == $deleteStatus) {
        /**
         * Delete directory from S3.
         */
        $deleteFileKey = $dirName;
        $result = $awsSdkObj->deleteDirectoryFilesFromS3($bucketName, $deleteFileKey);
        unset($dirName);
    } else {
        die('Wrong parameter passed for object deletion!');
    }

    if ($result['success']) {
        echo $result['msg'] . '<br><br>';
    }
}

if (isset($dirName) && !empty($dirName) && isset($bucketName) && !empty($bucketName)) {
    /**
     * List all existing file inside the requested directory inside S3 bucket.
     */
    $result = $awsSdkObj->getFileList($bucketName, $dirName);
    if (count($result['fileList']) > 0) {
        $files = $result['fileList'];
        ?>
                Following files are uploaded in <?php echo $dirName; ?> directory:<br>
                <ul>
        <?php
        foreach ($files as $k => $v) {
            echo '<li>';
            echo($k + 1) . ') ' . $v;
            $filePath = $dirName . '/' . $v;
            ?>
                        <!-- Showing live links of all existing files inside S3 bucket directory -->
                        [ <a href="<?php echo $awsSdkObj->getS3UrlPath($bucketName) . $filePath; ?>" target="_BLANK">S3 Link</a> ] 
                        [ <a href="?delete=1&directory=<?php echo urlencode($dirName); ?>&file_key=<?php echo urlencode($filePath); ?>&bucket=<?php echo urlencode($bucketName); ?>">Delete</a> ] 
                        <?php
                        echo '</li>';
                    }
                    ?>
                </ul>
                <br>
                <a href="?delete=2&directory=<?php echo urlencode($dirName); ?>&bucket=<?php echo urlencode($bucketName); ?>">Delete Directory?</a>
                <?php
            }
        }
        ?>
    </body>
</html>

<?php

/**
 * PHP delete function that deals with directories recursively.
 *
 * @param string $target Target path to directory
 * 
 * @return bool Boolean value TRUE
 */
function deleteFiles($target) {
    if (is_dir($target)) {
        $files = glob($target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

        foreach ($files as $file) {
            deleteFiles($file);
        }

        rmdir($target);
    } elseif (is_file($target)) {
        unlink($target);
    }

    return true;
}

/**
 * PHP create function that creates a directory locally.
 *
 * @param string $target Target directory path
 * 
 * @return string $target Received parameter value returned as it is 
 */
function createDirectory($target) {
    if (!file_exists($target) && !is_dir($target)) {
        if (!mkdir($target, 0777, true)) {
            die('Folder cannnot be created. Please check folder access permission!');
        }
    }

    return $target;
}

/**
 * Upload error messages based on error type PHP.
 *
 * @param string $code Upload error code
 * 
 * @return string Code message
 */
function uploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';

            break;
        case UPLOAD_ERR_FORM_SIZE:
            $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';

            break;
        case UPLOAD_ERR_PARTIAL:
            $message = 'The uploaded file was only partially uploaded';

            break;
        case UPLOAD_ERR_NO_FILE:
            $message = 'No file was uploaded';

            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $message = 'Missing a temporary folder';

            break;
        case UPLOAD_ERR_CANT_WRITE:
            $message = 'Failed to write file to disk';

            break;
        case UPLOAD_ERR_EXTENSION:
            $message = 'File upload stopped by extension';

            break;
        default:
            $message = 'Unknown upload error';

            break;
    }

    return $message;
}
