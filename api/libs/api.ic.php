<?php

/**
 * IceCream - Produces fast and clear debug output.
 * 
 * ic() is like print(), but better:
 *
 *  - It prints both expressions/variable/methods/functions names and their values.
 *  - It's 60% faster to type.
 *  - Data structures are pretty printed.
 *  - It optionally includes program context: filename, line number, and parent function.
 * 
 * More details: https://github.com/gruns/icecream
 * 
 * @param mixed ...$values
 * 
 * @return mixed
 */
function ic(...$values) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0];
    $inside = $backtrace[1] ? $backtrace[1] :  null;
    $fileName = basename($caller['file']);
    $fileLine = $caller['line'];
    $titlePrefix = 'ic | ';
    $outputFunction = (function_exists('show_window'))  ? 'show_window' : '';

    if ($values === array()) {
        $string = basename($caller['file']) . ":{$caller['line']}";
        if (isset($inside['class'])) {
            $class = strpos($inside['class'], 'class@') === 0 ? 'class@anonymous' : $inside['class'];
            $string .= " in {$class}{$inside['type']}{$inside['function']}()";
        } elseif (isset($inside['function'])) {
            $string .= " in {$inside['function']}()";
        }

        $microtime = microtime();
        $microtime = explode(' ', $microtime);
        $string .= ' at ' . date("H:i:s") . '.' . round($microtime[0] * 1000);

        if ($outputFunction) {
            $outputFunction($titlePrefix . $fileName . ' line ' . $fileLine, $string);
        } else {
            print($titlePrefix . $fileName . ' line ' . $fileLine . PHP_EOL . $string . PHP_EOL);
        }
        return (null);
    }

    $fileContent = file_get_contents($caller['file']);
    $tokens = token_get_all($fileContent, TOKEN_PARSE);

    $tokenCount = count($tokens);
    $functionNameIndex = null;
    $functionUsageIndexes = array();

    // STEP 1: Find the function name
    // e.g. ic('foo')
    //      ^^
    // The first token will always be the opening tag, or HTML before the opening tag, so we can safely skip it
    for ($i = 1; $i < $tokenCount; ++$i) {
        $token = $tokens[$i];

        if (! is_array($token)) {
            continue;
        }

        if ($token[2] > $caller['line']) {
            break;
        }

        if ($token[0] === T_STRING && strtolower($token[1]) === 'ic') {
            $functionUsageIndexes[] = $i;
        }
    }

    $functionNameIndex = end($functionUsageIndexes);
    $openBraceIndex = null;

    // STEP 2: Find the function call opening brace
    // e.g. ic('foo')
    //        ^
    for ($i = $functionNameIndex + 1; $i < $tokenCount; ++$i) {
        if ($tokens[$i] === '(') {
            $openBraceIndex = $i;
            break;
        }
    }

    $depth = 0;
    $contents = array('');
    $current = 0;

    // STEP 3: Find all the tokens between the opening brace and the closing brace
    // e.g. ic('foo')
    //         ^^^^^
    for ($i = $openBraceIndex + 1; $i < $tokenCount; ++$i) {
        $token = $tokens[$i];
        if ($token === '[' || $token === '{' || $token === '(') {
            ++$depth;
        }

        if ($token === ']' || $token === '}') {
            --$depth;
        }

        if ($token === ')') {
            if ($depth === 0) {
                break;
            }
            --$depth;
        }

        if ($depth === 0 && $token === ',') {
            ++$current;
            $contents[$current] = '';
            continue;
        }

        if (! is_array($token)) {
            $contents[$current] .= $token;
            continue;
        }

        $type = $token[0];

        if ($type === T_COMMENT || $type === T_DOC_COMMENT) {
            continue;
        }

        if ($type === T_WHITESPACE) {
            $contents[$current] .= ' ';
            continue;
        }

        $contents[$current] .= $token[1];
    }

    $strings = array();

    foreach ($contents as $i => $content) {
        $strings[] = trim($content) . ': ' . trim(print_r($values[$i], true));
    }

    if ($outputFunction) {
        $outputFunction($titlePrefix . $fileName . ' line ' . $fileLine, '<pre>' . print_r(implode(', ', $strings), true) . '</pre>');
    } else {
        print($titlePrefix . $fileName . ' line ' . $fileLine . PHP_EOL . print_r(implode(', ', $strings), true) . PHP_EOL);
    }
    $result = (count($values) === 1) ? $values[0] : $values;
    return ($result);
}
