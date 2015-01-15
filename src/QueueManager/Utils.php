<?php
/**
 * @author Vojta Biberle
 * @since 12.1.15
 */

namespace QueueManager;


class Utils {

    const DSN_REGEX = '/^(?P<protocol>\w+):\/\/((?P<user>\w+)(:(?P<password>\w+))?@)?(?P<host>[.\w]+)(:(?P<port>\d+))?\/(?P<database>\w+)$/im';
    static function ParseDsn($dsn)
    {
        $result = array
        (
            'protocol' => '',
            'user' => '',
            'password' => '',
            'host' => 'localhost',
            'port' => '',
            'database' => ''
        );
        if (strlen($dsn) == 0)
        {
            return false;
        }
        if (!preg_match(self::DSN_REGEX, $dsn, $matches))
        {
            return false;
        }
        if (count($matches) == 0)
        {
            return false;
        }
        foreach ($result as $key => $value)
        {
            if (array_key_exists($key, $matches) and !empty($matches[$key]))
            {
                $result[$key] = $matches[$key];
            }
        }
        return $result;
    }

    static function JoinDsn($dsn)
    {
        $result = '';

        $result .= !empty($dsn['protocol']) ? $dsn['protocol'].'://' : '';
        $result .= !empty($dsn['user']) ? $dsn['user'] : '';
        $result .= !empty($dsn['password']) ? ':'.$dsn['password'] : '';
        $result .= !empty($dsn['user']) ? '@' : '';
        $result .= $dsn['host'];
        $result .= !empty($dsn['port']) ? ':'.$dsn['port'] : '';

        return $result;
    }
}