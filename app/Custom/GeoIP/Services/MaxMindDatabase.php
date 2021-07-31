<?php

namespace App\Custom\GeoIP\Services;

use Exception;
use GeoIp2\Database\Reader;
use Torann\GeoIP\Services\MaxMindDatabase as Service;

class MaxMindDatabase extends Service
{

    /**
     * The "booting" method of the service.
     *
     * @return void
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function boot()
    {
        $this->reader = new Reader(
            $this->config('database_path'),
            $this->config('locales', ['en'])
        );
    }

    public function update()
    {
        if ($this->config('database_path', false) === false) {
            throw new Exception('Database path not set in config file.');
        }

        $this->withTemporaryDirectory(function ($directory) {
            $tarFile = sprintf('%s/maxmind.tar.gz', $directory);
            file_put_contents($tarFile, fopen($this->config('update_url'), 'r'));
            $handle = popen("tar -xzvf {$tarFile} -C {$directory} 2>&1", 'r');
            $tmpFile = '';
            while(!feof($handle)) {
                set_time_limit(3);
                $buffer = trim(fgets($handle) , "\n");
                if (pathinfo($buffer, PATHINFO_EXTENSION) === 'mmdb')
                {
                    $tmpFile = $directory.'/'.$buffer;
                }
                flush();
            }
            pclose($handle);
            if(empty($tmpFile))
            {
                throw new Exception('Database file could not be found within archive.');
            }
            file_put_contents($this->config('database_path'), fopen("$tmpFile", 'r'));
        });

        return "Database file ({$this->config('database_path')}) updated.";
    }

}
