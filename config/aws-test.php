<?php

use Aws\Laravel\AwsServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. This file
    | is published to the application config directory for modification by the
    | user. The full set of possible options are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    */
    'region' => env('AWS_REGION', 'us-east-1'),
    'version' => 'latest',
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],

    'Kinesis' => [
        'region' => env('AWS_KINESIS_REGION', 'ap-southeast-1'),
        'profile' => env('AWS_KINESIS_PROFILE', 'default'),
        'version' => env('AWS_KINESIS_VERSION', '2013-12-02'),
    ],
    'Pinpoint' => [
        'region' => env('AWS_PINPOINT_REGION', 'ap-southeast-1'),
        'profile' => env('AWS_PINPOINT_PROFILE', 'default'),
        'version' => env('AWS_PINPOINT_VERSION', 'latest'),
        'credentials' => [
            'key' => env('AWS_PINPOINT_KEY', ''),
            'secret' => env('AWS_PINPOINT_SECRET', '')
        ]
    ],
    'CognitoIdentity' => [
        'region' => env('AWS_COGNITO_IDENTITY_REGION', 'ap-southeast-1'),
//        'profile' => env('AWS_COGNITO_IDENTITY_PROFILE', 'default'),
        'version' => env('AWS_COGNITO_IDENTITY_VERSION', 'latest'),
        'credentials' => [
            'key' => env('AWS_COGNITO_IDENTITY_KEY', ''),
            'secret' => env('AWS_COGNITO_IDENTITY_SECRET', '')
        ]
    ],
    'Sts' => [
        'region' => env('AWS_STS_REGION', 'ap-southeast-1'),
        'profile' => env('AWS_STS_PROFILE', 'default'),
        'version' => env('AWS_STS_VERSION', 'latest'),
        'credentials' => [
            'key' => env('AWS_STS_KEY', ''),
            'secret' => env('AWS_STS_SECRET', '')
        ]
    ],


];
