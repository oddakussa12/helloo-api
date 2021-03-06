<?php
namespace App\Providers;

use League\Flysystem\Filesystem;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Overtrue\Flysystem\Qiniu\Plugins\FileUrl;
use Overtrue\Flysystem\Qiniu\Plugins\FetchFile;
use Overtrue\Flysystem\Qiniu\Plugins\UploadToken;
use Overtrue\Flysystem\Qiniu\Plugins\RefreshFile;
use Overtrue\Flysystem\Qiniu\Plugins\PrivateDownloadUrl;
use Overtrue\LaravelFilesystem\Qiniu\QiniuStorageServiceProvider as QiniuBaseServiceProvider;

class QiniuStorageServiceProvider extends QiniuBaseServiceProvider
{
    public function register()
    {
        app('filesystem')->extend('qn_default', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });
        app('filesystem')->extend('qn_avatar', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });
        app('filesystem')->extend('qn_avatar_sia', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });
//        app('filesystem')->extend('qn_cover', function ($app, $config) {
//            $adapter = new QiniuAdapter(
//                $config['access_key'], $config['secret_key'],
//                $config['bucket'], $config['domain']
//            );
//
//            $flysystem = new Filesystem($adapter);
//            $flysystem->addPlugin(new FetchFile());
//            $flysystem->addPlugin(new UploadToken());
//            $flysystem->addPlugin(new FileUrl());
//            $flysystem->addPlugin(new PrivateDownloadUrl());
//            $flysystem->addPlugin(new RefreshFile());
//            return $flysystem;
//        });
//        app('filesystem')->extend('qn_subtitle', function ($app, $config) {
//            $adapter = new QiniuAdapter(
//                $config['access_key'], $config['secret_key'],
//                $config['bucket'], $config['domain']
//            );
//
//            $flysystem = new Filesystem($adapter);
//            $flysystem->addPlugin(new FetchFile());
//            $flysystem->addPlugin(new UploadToken());
//            $flysystem->addPlugin(new FileUrl());
//            $flysystem->addPlugin(new PrivateDownloadUrl());
//            $flysystem->addPlugin(new RefreshFile());
//            return $flysystem;
//        });
//        app('filesystem')->extend('qn_video', function ($app, $config) {
//            $adapter = new QiniuAdapter(
//                $config['access_key'], $config['secret_key'],
//                $config['bucket'], $config['domain']
//            );
//
//            $flysystem = new Filesystem($adapter);
//            $flysystem->addPlugin(new FetchFile());
//            $flysystem->addPlugin(new UploadToken());
//            $flysystem->addPlugin(new FileUrl());
//            $flysystem->addPlugin(new PrivateDownloadUrl());
//            $flysystem->addPlugin(new RefreshFile());
//            return $flysystem;
//        });
        app('filesystem')->extend('qn_image', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });

        app('filesystem')->extend('qn_image_sia', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });
        app('filesystem')->extend('qn_video_sia', function ($app, $config) {
            $adapter = new QiniuAdapter(
                $config['access_key'], $config['secret_key'],
                $config['bucket'], $config['domain']
            );

            $flysystem = new Filesystem($adapter);
            $flysystem->addPlugin(new FetchFile());
            $flysystem->addPlugin(new UploadToken());
            $flysystem->addPlugin(new FileUrl());
            $flysystem->addPlugin(new PrivateDownloadUrl());
            $flysystem->addPlugin(new RefreshFile());
            return $flysystem;
        });
    }

    public function boot()
    {
        return parent::boot(); // TODO: Change the autogenerated stub
    }
}