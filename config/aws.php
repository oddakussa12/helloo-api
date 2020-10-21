<?php

return [
  'Kinesis'=>[
      'region'=>env('AWS_KINESIS_REGION' , 'ap-southeast-1'),
      'profile'=>env('AWS_KINESIS_PROFILE' , 'default'),
      'version'=>env('AWS_KINESIS_VERSION' , '2013-12-02'),
  ]
];