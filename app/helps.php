<?php
if (! function_exists('locale')) {
    function locale($locale = null)
    {
        if (is_null($locale)) {
            return app()->getLocale();
        }
        app()->setLocale($locale);
        return app()->getLocale();
    }
}

if(!function_exists('supportedLocales')){
    function supportedLocales()
    {
        return LaravelLocalization::getSupportedLocales();
    }
}

if(!function_exists('dynamicSetLocales')){
    function dynamicSetLocales($langs = array())
    {
        $locales = config('translatable.locales');
        if(!empty($langs)&&is_array($langs))
        {
            foreach ($langs as $lang)
            {
                if(!in_array($lang , $locales))
                {
                    array_push($locales , $lang);
                }
            }
            app('config')->set('translatable.locales', $locales);
        }
    }

}

if(!function_exists('notify')){
    function notify($category = 'user.like' , $data = array() , $anonymous=false)
    {
        $params = array('from'=>'' , 'to'=>'' , 'extra'=>array() , 'url'=>'' , 'expire'=>'' , 'setField'=>array());
        $op = array_intersect_key($data , $params);
        $notifynder = Notifynder::category($category);
        if($anonymous)
        {
            unset($op['from']);
            $notifynder->anonymous();
        }
        foreach ($op as $k=>$v)
        {
            $notifynder->{$k}($v);
        }

        $notifynder->send();
//            ->from(54)
//            ->to(2)
//            ->extra(['message' => 'Hey John, I\'m Doe.']) // define additional data
//            ->extra(['action' => 'invitation'], false) // extend additional data
//            ->url('http://laravelacademy.org/notifications')
//            ->send();
    }
}

if (!function_exists('rate')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param int       $likes
     * @param timestamp $created
     *
     * @return float
     */
    function rate($likes, $created)
    {
        $startTime = 1473696439; // strtotime('2016-09-12 16:07:19')
        $created = strtotime($created);
        $timeDiff = $created - $startTime;

        $x = $likes;

        if ($x > 0) {
            $y = 1;
        } elseif ($x == 0) {
            $y = 0;
        } else {
            $y = -1;
        }

        if (abs($x) >= 1) {
            $z = abs($x);
        } else {
            $z = 1;
        }

        return (log10($z) * $y) + ($timeDiff / 45000);
    }
}

if (!function_exists('getRequestIpAddress')) {
    /**
     * Returns the real IP address of the request even if the website is using Cloudflare.
     *
     * @return string
     */
    function getRequestIpAddress()
    {
        $realip = '0.0.0.0';
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }
        return $realip;
    }
}

if (!function_exists('rate_comment')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param int       $likes
     * @param timestamp $created
     *
     * @return float
     */
    function rate_comment($comments, $created)
    {
        $startTime = 1556728818; // strtotime('2016-09-12 16:07:19')
        
        $created = strtotime($created);
        $timeDiff = $created - $startTime;

        $x = $comments;

        if ($x > 0) {
            $y = 1;
        } elseif ($x == 0) {
            $y = 0;
        } else {
            $y = -1;
        }

        if (abs($x) >= 1) {
            $z = abs($x);
        } else {
            $z = 1;
        }

        return (log10($z) * $y) + ($timeDiff / 45000);
    }
}

