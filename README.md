# AWS-S3-filesystem-PHP-SDK
A simple PHP helper class to manipulate files in AWS S3 buckets using aws-sdk-php

## Getting Started

Before you begin, you need following things as requirement - 

* **AWS credentials** from your AWS account [see here](https://aws.amazon.com/developers/access-keys/)
* **PHP >= 5.5** [ Tested upto 7.2 ]
* **Install the SDK** – If you have already installed [Composer](https://getcomposer.org/) in your system / source directory 
  
   ```
   composer update
   ```

   Make sure to run above command where the `composer.json` file is located alongside `AwsS3Helper` helper class. 

   [ **Note** ] If your project already using composer autoload with other libraries, just append `"aws/aws-sdk-php": "^3.93"`
   in the *required* field of your `composer.json`

* Check inside the `AwsS3Helper` class file above if `vendor/autoload.php` is properly added as required script.
* Finally, configure you AWS **Access Key** and **Secret Key** in `config.ini` file. 


## Usage

```php

require_once 'AwsS3Helper.php';

// Get object of AwsS3Helper instance.
$awsSdkObj    = AwsS3Helper::getInstance();

// Execute upload function
$uploadResutl = $awsSdkObj->uploadFileToS3('s3-bucket-name', 'file-name', 'file-source-in-server-local', 'content-type-mime');

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
 
