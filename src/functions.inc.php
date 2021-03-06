<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 05.09.18
 * Time: 11:52
 */


/**
 * Pluck elements from arrays
 *
 * @param $key
 * @param $data
 * @param null $default
 * @return null
 * @throws Exception
 */
function phore_pluck ($key, &$data, $default=null)
{
    if (is_string($key) && strpos($key, ".") !== false) {
        $key = explode(".", $key);
    }

    if ( ! is_array($key))
        $key = [$key];

    if (count($key) === 0)
        return $data;

    $curKey = array_shift($key);
    if (! is_array($data) || ! array_key_exists($curKey, $data)) {
        if ($default instanceof Exception)
            throw $default;
        return $default;
    }
    $curData =& $data[$curKey];
    return phore_pluck($key,$curData, $default);
}

function startsWith($haystack, $needle) : bool
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) : bool
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

/**
 * Unindent (strip trailing whitespace) string
 *
 * @param string $text
 * @return string
 */
function phore_text_unindent(string $text) : string {
    if ( ! preg_match('/\n([ \t]*)\S+/', $text, $matches)) {
        return $text;
    }
    return trim(str_replace("\n" . $matches[1], "\n", $text));
}



function phore_format() : \Phore\Core\Format\PhoreFormat
{
    return new \Phore\Core\Format\PhoreFormat();
}


/**
 * Transform the input array into another array using the callback function
 * applied on each element of $input
 *
 * @param array $input
 * @param callable $callback
 * @return array
 */
function phore_array_transform (array $input, callable $callback) : array
{
    $out = [];
    foreach ($input as $key => $value) {
        $ret = $callback($key, $value);
        if ($ret === null)
            continue;
        $out[] = $ret;
    }
    return $out;
}


/**
 * Escape parameters joined into a string using a escaper function
 *
 * @param string $cmd
 * @param array $params
 * @param callable $escaperFn
 * @return string
 */
function phore_escape (string $cmd, array $args, callable $escaperFn) : string
{
    $argsCounter = 0;
    $cmd = preg_replace_callback( '/\?|\:[a-z0-9_\-]+|\{[a-z0-9_\-]+\}/i',
        function ($match) use (&$argsCounter, &$args, $escaperFn) {
            if ($match[0] === '?') {
                if(! isset($args[$argsCounter])){
                    throw new \Exception("Index $argsCounter missing");
                }
                $argsCounter++;
                return escapeshellarg(array_shift($args));
            }
            if ($match[0][0] === "{") {
                $key = substr($match[0], 1, -1);
            } else {
                $key = substr($match[0], 1);
            }
            if (!isset($args[$key])){
                throw new \Exception("Key '$key' not found");
            }
            return $escaperFn($args[$key], $key);
        },
        $cmd);
    return $cmd;
}

/*
 * Output a message to the defined channel including timing information
 *
 * @param $msg
 * @param bool $return
 */
function phore_out($msg=null, $return = false) {
    static $lastTime = null;
    static $firstTime = null;
    if ($lastTime === null) {
        $lastTime = $firstTime = microtime(true);
    }
    $str = "\n[" . number_format((microtime(true) - $firstTime), 3, ".", "") . "+" . number_format((microtime(true) - $lastTime), 3, ".", "") . "s] $msg";
    $lastTime = microtime(true);
    if ($return === true)
        return $str;
    echo $str;
}


/**
 * Print json nicely
 * 
 * @param $json
 * @return string
 */
function phore_json_pretty_print(string $json) : string
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                $level--;
                $ends_line_level = NULL;
                $new_line_level = $level;
                break;

                case '{': case '[':
                $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                $char = "";
                $ends_line_level = $new_line_level;
                $new_line_level = NULL;
                break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;
}



function phore_json_encode($input) : string
{
    return json_encode($input, JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * @param string $input
 * @return array
 * @throws InvalidArgumentException
 */
function phore_json_decode(string $input) : array
{
    $ret = json_decode($input, true, 512, JSON_PRESERVE_ZERO_FRACTION);
    if ($ret === null)
        throw new InvalidArgumentException("Cannot json_decode() input data: " . json_last_error_msg());
    if ( ! is_array($ret))
        throw new InvalidArgumentException("phore_json_decode(): Simple data import (string, int, bool) not supported.");
    return $ret;
}

