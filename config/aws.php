<?php

return [
    'Kinesis'=>[
        'region'=>env('AWS_KINESIS_REGION' , 'ap-southeast-1'),
        'profile'=>env('AWS_KINESIS_PROFILE' , 'default'),
        'version'=>env('AWS_KINESIS_VERSION' , '2013-12-02'),
    ],
    'Pinpoint'=>[
        'region'=>env('AWS_PINPOINT_REGION' , 'ap-southeast-1'),
        'profile'=>env('AWS_PINPOINT_PROFILE' , 'default'),
        'version'=>env('AWS_PINPOINT_VERSION' , 'latest'),
        'credentials'=>[
            'key'=>env('AWS_PINPOINT_KEY' , ''),
            'secret'=>env('AWS_PINPOINT_SECRET' , '')
        ]
    ]

];