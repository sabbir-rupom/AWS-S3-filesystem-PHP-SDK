# AWS-S3-filesystem-PHP-SDK
A simple PHP helper class to manipulate files in AWS S3 buckets using aws-sdk-php

## Getting Started

Before you begin, you need following things as requirement: 

* **AWS credentials** from your AWS account [see here](https://aws.amazon.com/developers/access-keys/)
* **PHP >= 5.5** [ Tested upto 7.2 ]
* **Install the SDK** – If you have already installed [Composer](https://getcomposer.org/) in your system / source directory
  
   ```
   composer update
   ```

   Make sure to run above command where the `composer.json` file is located

   [ **Note** ] If your project already using composer autoload with other libraries, just append `"aws/aws-sdk-php": "^3.93"`
   in the *required* field of your `composer.json`

* Check inside the `AwsS3Helper` class file above if `vendor/autoload.php` is properly added as required script
* Finally, configure your AWS **Access Key** and **Secret Key** in `config.ini` file


## Usage

```php

require_once '/--path to file--/AwsS3Helper.php';

// Get object of AwsS3Helper instance.
$awsSdkObj    = AwsS3Helper::getInstance();

// Execute upload function
$uploadResutl = $awsSdkObj->uploadFileToS3('s3-bucket-name', 'file-name', 'file-source-in-server', 'mime-content-type');

// Check file upload result
if (empty($uploadResutl)) {
    die('Failed to upload file in S3 Storage!');
} elseif (false == $uploadResutl['success']) {
    die('Failed to upload file in S3 Storage! ' . $uploadResutl['msg']);
} else {
    echo 'Upload successful ';
    echo 'URL: ' . $uploadResutl['url'];
}

```

## Functional Features

Featured functions are as follows:

* **uploadFileToS3()**
  * Upload file to specific bucket in S3 
  * Function accepts upto 5 parameters 
    * S3 Bucket Name
    * File name / Path to File name in S3 
    * File source in local server
    * Mime-content type of source file 
    * Public share flag, if `TRUE` uploaded file will be visible online 
* **checkBucketExistInS3()**
  * Checks if requested bucket exists in S3 or not
  * Function accepts 1 parameter
    * S3 Bucket Name
* **checkFileExistInS3()**
  * Checks if requested file exists in S3 or not
  * Function accepts 2 parameter
    * S3 Bucket Name
    * File name / Path to File name in S3
* **getS3UrlPath()**
  * Gets base URL path of a S3 bucket
  * Function accepts 1 parameter
    * S3 Bucket Name
* **getFileUrlInS3()**
  * Gets URL path of an object in S3 bucket
  * Function accepts 2 parameter
    * S3 Bucket Name
    * File name / Path to File name in S3
* **deleteFileFromS3()**
  * Deletes a file from S3 bucket
  * Function accepts 2 parameter
    * S3 Bucket Name
    * File name / Path to File name in S3
* **deleteDirectoryFilesFromS3()**
  * Deletes a directory from S3 bucket
  * Function accepts 2 parameter
    * S3 Bucket Name
    * Directory path in S3 
* **getBucketList()**
  * Get list of existing buckets in S3
  * Function accepts 1 parameter
    * Name-only flag, if `TRUE` only the bucket names are returned in array, else all information array of buckets are returned
* **getFileList()**
  * Get list of existing files from specific key-path in S3 bucket
  * Function accepts 2 parameter
    * S3 Bucket Name
    * Directory key-path in S3 
* **createBucket()**
  * Creates a bucket in S3
  * Function accepts 2 parameter
    * S3 Bucket Name, must be unique AWS regionally
    * Public share flag [ TRUE / FALSE ] 
* **getMimeType()**
  * Static function, does not need class instance to access
  * Gets the Mime-Content type of a file 
  * Function accepts 1 parameter
    * File name / Source path 

## Test Example

An example script is provided in `test` folder inside the repository, covers 80% of the total helper class usage.

## Author

* **Sabbir Hossain (Rupom)** - *Web Developer* - [https://sabbirrupom.com/](https://sabbirrupom.com/)
