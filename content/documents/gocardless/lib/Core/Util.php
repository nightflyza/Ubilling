<?php
namespace GoCardlessPro\Core;

abstract class Util
{
    /**
     * @param string|mixed $value A string to UTF8-encode.
     *
     * @return string|mixed The UTF8-encoded string, or the object passed in if it wasn't a string.
     */
    public static function utf8($value)
    {
        if (is_string($value) && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") {
            return utf8_encode($value);
        } else {
            return $value;
        }
    }

    /**
     * @param array       $arr    A map of param keys to values.
     * @param string|null $prefix
     *
     * @return string A querystring, essentially.
     */
    public static function encodeQueryParams($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = array();
        foreach ($arr as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix && $k && !is_int($k)) {
                $k = $prefix."[".$k."]";
            } elseif ($prefix) {
                $k = $prefix."[]";
            }

            if (is_array($v)) {
                $enc = self::encode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k)."=".urlencode($v);
            }
        }

        return implode("&", $r);
    }

    /**
     * Replace URL tokens with the substitution mapping to generate urls.
     *
     * For example:
     *
     *     subUrl("/stats_for/:id", array("id" => "foo")) => "/stats_for/foo"
     *
     * @param string $url           Url to substitute
     * @param array  $substitutions Substitutions to make
     *
     * @return string the generated URL
     */
    public static function subUrl($url, $substitutions)
    {
        foreach ($substitutions as $substitution_key => $substitution_value) {
            if (!is_string($substitution_value)) {
                $error_type = ' needs to be a string, not a '.gettype($substitution_value).'.';
                throw new \Exception('URL value for ' . $substitution_key . $error_type);
            }
            $url = str_replace(':' . $substitution_key, $substitution_value, $url);
        }
        return $url;
    }

}
