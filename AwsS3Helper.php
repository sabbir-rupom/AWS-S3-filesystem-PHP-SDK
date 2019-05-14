<?php

/**
 * A simple PHP helper class to manipulate files in AWS S3 buckets using aws-sdk-php library.
 *
 * @category S3-Filesystem-Helper
 * @package AWS-S3-filesystem-PHP-SDK
 * @author Sabbir Hossain Rupom <sabbir.hossain.rupom@hotmail.com>
 */
require_once 'vendor/autoload.php'; // Require the Composer autoloader.

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

/**
 * S3 file system helper class.
 */
class AwsS3Helper {

    protected $config;
    protected $result;
    // Define member variables
    private $_s3Client;
    // Hold an instance of the class
    private static $_instance;

    /**
     * Initiate helper class constructor.
     */
    private function __construct() {
        // Initialize member variables
        $this->_s3Client = new S3Client($this->_getConfig());
        $this->result = [
            'success' => false,
            'msg' => '',
        ];
    }

    /**
     * The singleton method.
     *
     * @return obj single class instance
     */
    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get S3 connection object.
     *
     * @return obj AWS S3 Client
     */
    public function getS3Client() {
        return $this->_s3Client;
    }

    /**
     * Upload file in AWS S3 storage server.
     *
     * @param string $bucketName   Name of S3 bucket
     * @param string $filekey      full pathname of AWS file
     * @param string $fileLocation File source location in local directory
     * @param string $contentType  Type of file-content
     * @param bool   $public       Set public status true/false for uploaded file
     *
     * @return array [] Result array
     *               [fileUrl]       string Live url of uploaded object, if uploaded successfully
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function uploadFileToS3($bucketName, $filekey, $fileLocation, $contentType, $public = true) {
        try {
            $configArray = [
                'Bucket' => $bucketName,
                'Key' => $filekey,
                'SourceFile' => $fileLocation,
                'ACL' => $public ? 'public-read' : 'authenticated-read',
                'StorageClass' => 'REDUCED_REDUNDANCY',
            ];
            if (!empty($contentType)) {
                $configArray['ContentType'] = $contentType;
            }

            $result = $this->_s3Client->putObject($configArray);
            $this->result['fileUrl'] = $this->_s3Client->getObjectUrl($bucketName, $filekey);
            $this->result['success'] = true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Check object file exist or not.
     *
     * @param string $bucketName Name of the S3 bucket
     *
     * @return bool Return TRUE if object/file exist, otherwise FALSE
     */
    public function checkBucketExistInS3($bucketName) {
        try {
            $result = $this->_s3Client->headBucket(
                    [
                        'Bucket' => $bucketName,
                    ]
            );
            $this->result['success'] = true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result['success'];
    }

    /**
     * Check object file exist or not.
     *
     * @param string $bucketName Name of the S3 bucket
     * @param string $fileKey    File path string in S3 bucket
     *
     * @return bool Return TRUE if object/file exist, otherwise FALSE
     */
    public function checkFileExistInS3($bucketName, $fileKey) {
        return $this->_s3Client->doesObjectExist($bucketName, $fileKey);
    }

    /**
     * Get base URL path of objects in S3 bucket.
     *
     * @param string $bucketName Name of the S3 bucket
     *
     * @return string Base URL of s3 object in bucket
     */
    public function getS3UrlPath($bucketName) {
        return 'https://' . $bucketName . '.s3.' . $this->config['AWS_REGION'] . '.amazonaws.com/';
    }

    /**
     * Delete specific file from AWS S3 storage server.
     *
     * @param string $bucketName Name of the S3 bucket
     * @param string $fileKey    S3 file path to object
     *
     * @return array [] Result array
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function deleteFileFromS3($bucketName, $fileKey) {
        try {
            if ($this->checkFileExistInS3($bucketName, $fileKey)) {
                $result = $this->_s3Client->deleteObject(
                        [
                            'Bucket' => $bucketName,
                            'Key' => $fileKey,
                        ]
                );
            }
            $this->result['success'] = true;
            $this->result['msg'] = 'File has been deleted from AWS S3 successfully';
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Delete all files from specified path of AWS S3 storage server.
     *
     * @param string $bucketName Name of the S3 bucket
     * @param string $dirPrefix  Directory path under which files needed to be checked
     *
     * @return array [] Result array
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function deleteDirectoryFilesFromS3($bucketName, $dirPrefix) {
        try {
            $results = $this->_s3Client->listObjectsV2(
                    [
                        'Bucket' => $bucketName,
                        'Prefix' => $dirPrefix,
                    ]
            );

            if (isset($results['Contents'])) {
                foreach ($results['Contents'] as $result) {
                    $this->_s3Client->deleteObject(
                            [
                                'Bucket' => $bucketName,
                                'Key' => $result['Key'],
                            ]
                    );
                }
            }

            $this->result['success'] = true;
            $this->result['msg'] = 'Directory has been deleted from AWS S3 successfully';
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Get all bucket list in AWS S3.
     *
     * @param bool $nameOnly Set flag true if only bucket name is wanted, otherwise all bucket information will be provided
     *
     * @return array [] Result array
     *               [bucketList]    Bucket names / information array
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function getBucketList($nameOnly = true) {
        try {
            $result = $this->_s3Client->listBuckets();
            $this->result['bucketList'] = [];

            foreach ($result->toArray()['Buckets'] as $val) {
                $this->result['bucketList'][] = $nameOnly ? $val['Name'] : $val;
            }

            $this->result['success'] = true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Get all file/object list from specific path in AWS S3 bucket.
     *
     * @param mixed $bucketName Name of the S3 bucket
     * @param mixed $dirPrefix  Directory path string in S3 bucket
     *
     * @return array [] Result array
     *               [fileList]      List of file names
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function getFileList($bucketName, $dirPrefix) {
        try {
            $this->result['fileList'] = [];

            $objects = $this->_s3Client->listObjects(
                    [
                        'Bucket' => $bucketName,
                        'Prefix' => $dirPrefix,
                    ]
            );

            if (!empty($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    // Prepare file name from full path key
                    $fileKey = $object['Key'];
                    $idx = explode('/', $fileKey);
                    $count_explode = count($idx);
                    $fileName = strtolower($idx[$count_explode - 1]);

                    $this->result['fileList'][] = $fileName;
                }
            }

            $this->result['success'] = true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Create new bucket in AWS S3.
     *
     * @param string $name   Name of the bucket
     * @param bool   $public Public read status in live
     *
     * @return array [] Result array
     *               [success]       bool Success result
     *               [msg]           string Exception message if occurred during function execution
     */
    public function createBucket($name, $public = true) {
        $name = strtolower($name);

        try {
            if (false == $this->checkBucketExistInS3($name)) {
                $result = $this->_s3Client->createBucket(
                        [
                            'Bucket' => $name, // REQUIRED
                            'ACL' => $public ? 'public-read' : 'authenticated-read',
                        ]
                );
            }
            $this->result['success'] = true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->result['msg'] = $e->getMessage();
            $this->result['success'] = false;
        }

        return $this->result;
    }

    /**
     * Retrieve file content type from filename.
     *
     * @param string $filename Name of file with extension
     *
     * @return string File Mime Type [ if not matched default mime-type returned 'application/octet-stream' ]
     */
    public static function getMimeType($filename) {
        $idx1 = explode('.', $filename);
        $count_explode = count($idx1);
        $idx = strtolower($idx1[$count_explode - 1]);

        $mimet = [
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // Images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // Archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // Audio/video
            'mpg' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'audio/mp4',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'aac' => 'audio/aac',
            // Adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // MS Office
            'doc' => 'application/msword',
            'dot' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xla' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pot' => 'application/vnd.ms-powerpoint',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppa' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'mdb' => 'application/vnd.ms-access',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if (isset($mimet[$idx])) {
            return $mimet[$idx];
        }

        return 'application/octet-stream';
    }

    /**
     * Get AWS SDK configuration settings.
     *
     * @return array [] Retrieve SDK configuration array
     *               [version]       SDK version
     *               [region]        Used AWS Region
     *               [credentials]   AWS Authentication Token Array
     *               [key]       AWS Access Token
     *               [secret]    AWS Secret Access Key
     */
    private function _getConfig() {
        if (!file_exists(__DIR__ . '/config.ini')) {
            die("SDK configuration failed. 'config.ini' not found.");
        }

        $this->config = parse_ini_file(__DIR__ . '/config.ini');

        if (empty($this->config['AWS_REGION']) || empty($this->config['ACCESS_TOKEN']) || empty($this->config['SECRET_KEY'])) {
            die("Configuration failed. Settings data is empty - please check 'config.ini' file");
        }

        // Initiate config parameters for AWS SDK library
        return [
            'version' => 'latest',
            'region' => $this->config['AWS_REGION'],
            'credentials' => [
                'key' => $this->config['ACCESS_TOKEN'],
                'secret' => $this->config['SECRET_KEY'],
            ],
        ];
    }

}
