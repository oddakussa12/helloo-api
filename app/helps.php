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
	//echo 1;die;
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
            if($k=='setField')
            {
                $notifynder->{$k}($v[0] , $v[1]);
            }else{
                $notifynder->{$k}($v);
            }
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

if(!function_exists('notify_remove')){
    function notify_remove($category_id , $object)
    {
        $contact_id = $object->{$object->getKeyName()};
        if(!is_array($category_id))
        {
            $category_id = array($category_id);
        }
        $object->owner->getNotificationRelation()->where(function($query) use ($contact_id , $category_id){
            $query
                ->where('from_id' , auth()->id())
                ->where('contact_id' , $contact_id)
                ->whereIn('category_id' , $category_id);

        })->delete();
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
     * @param $comments
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

if (!function_exists('rate_comment_v2')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param $comments
     * @param $create_time
     * @param int $likes
     * @param float $gravity
     * @return float
     */
    function rate_comment_v2($comments, $create_time, $likes=0 , $gravity = 1.5)
    {
        $ctime = strtotime($create_time);
        return ($likes + $comments + 1) / pow(floor((time()-$ctime)/3600) + 2, $gravity);
    }
}

if (!function_exists('domains')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param string $item
     * @return float
     */

    function domain($domain=null,$item='host')
    {
        if($domain==null){
            $url = parse_url(url()->current());
        }else{
            $url = parse_url($domain);
        }
        return $url[$item];
    }
}





