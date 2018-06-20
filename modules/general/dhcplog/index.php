<?php

if (cfr('DHCP')) {
    require_once('api/vendor/bf/Brainfuck.php');
    $bf = new dotzero\Brainfuck();
    $code = '+++++++++++++++++++++++++[>++>+++>++++>+++++<<<<-]++++++++++++++++++++++++++++++++....++++.>>>>-----------.<+.>+.++.<+++++++.>-.<<<<----.>+++++++++++.<.+++++++..>--.<-----------------------------.++++++++++++++++++++++....++++.>>>+.++.-----------.>+.<++++++++.-------.<<++++++++.>>>------.-.++++++.--.---.---.+++++++.<<<<----.>------.<.>>>>++++.<+.-------.<<+++++.>>++.++.++++++++.<+.>--.+++++.---.<<<++++++++.-.>---.>>++.++.-----------.>--.<++++++++.-------.<<--.>>-.++++.-----.>-----.<<<<.++.>--.<-------------------------------.++++++++++++++++++++++....>>>>+++.<+++++.>----.++++++++.<---------.>.<++++++++++.+++++.----------.>--------.++++++++.<<<<++++++++.-..+++++.------------.++++.>>>+++++++++.++.-----------.>--.<++++++++.-------.<<++++++++.>>>------.-.++++++.--.---.---.+++++++.<<<<+++++.>--------.<-------------------------------.++++++++++++++++++++++....++++.>>>--.>----.-.<+++.+++.--.<+++.>-----.>----.<<<++.>>>++++.<+++.>+++++++++.<<<<----.>>++++++.>---.+++++++.+++..---.+++++.-------.<<++++++.>>++++++++.-.--------.+++.--.<<<++++++++.+.>--------.<-------------------------------.++++++++++++++++++++++....++++.>>>-----.+++++++.+++..<<++++++++.>>------.+.<<<----.>------.<.++++.>>>----.>--------.-.<+++.+++.--.<------.>-----.>----.<<<<+++++++++.>+.>>>---.--.+++++++++++++++.<<<++++.>>+++++++.+++..---.+++++.-------.<<<-----.+.>-------.<-------------------------------.++++++++++++++++++++++....++++.>>>------.>--------.++++++++.<<<++++++++.>>+++++.+.<<<----.>------.<.++++.>>>----.>-----.-.<+++.+++.--.<.>-----.>----.<<<<+++++++++.>+.>>>---.--.+++++++++++++++.<<<+++.>>>--------.++++++++.<+++.>--.<<<<-----.+.>------.<-------------------------------..++++++++++++++++++++++....++++.>>>--.--.>++.<<+.>.>.<+++++++.<<<----.>++.<.++++.>>>------.+++++++.+++..<<++++++.>>------.+.<+++++++++++.<<+++.>.--.>-------.<<.>>+++++++++.<------.<-----------------------------.++++++++++++++++++++++....++++.>>>.>--.<--.>--.<<-------------.>----.>++++.<+++++++.<<<----.>++.<.++++.>>>------.+++++++.+++..<<++++++.>>------.+.<+++++++++++.<<+++.>++++.>---------.<--.>--.<<.>>>----------.<<----------.<-----------------------------.++++++++++++++++++++++....++++.>>>>.<++++.++++++++.+++.<.>-----------.>.<+++++++.<<<----.>++.<.++++.>>>------.+++++++.+++..<<++++++.>>------.+.<+++++++++++.<<+++.>>-------.<--.++++++++.+++.<.>>+++++++++.<-----------------.<-----------------------------.++++++++++++++++++++++....++++.>>>>-.++.<---.>------.<<-------------.>---.>+++++.<+++++++.<<<----.>++.<.++++.>>>------.+++++++.+++..<<++++++.>>------.+.<+++++++++++.<<+++.>>--------.++.<+.>------.<<.>>>----------.<<---------.<-----------------------------.++++++++++++++++++++++....++++.>>>>--------.+++.--------.<<+.>++++.>+++++++++++++.<+++++++.<<<----.>++.<.++++.>>>-------.>--------.++++++++.<<<++++++.>>+++++.+.<+++++++++++.<<+++.>+++++++++++.-.-.-------.----.>--------.<++++.>.<<.>>++++++++++.<----------.<-----------------------------.++++++++++++++++++++++....++++.>>>+++++.---.+++++.<++++++++.>>-.<<<+++++++++++++++++.>++++.>-.<.>>+.<<<<----.>---------------.<.>-----------.--..+++++++++++.<----------------------.++++++++++++++++++++++....>>.---.<<.++++++++.>>>>+++.<<.-------.<++++++++.>>-----.---.--.++++++++.<<++++.>++++++.>>---.<<<<.>>----.>>--..<<.>>+++++++.<<<<.-.>>+++.>++++.>--.<-.--.+++.<---.+++.++.>>--.<---..<<<.++...---------.>>>>++++++.<<<<----------------------.++++++++++++++++++++++........++++.>>>+.++.>-----.<<-.<----.>>.--..<----.>+.<+++.<<----.>------.<.++++.>>>>---.++.<<.>+.<<+++++++++++++++++++.>---.>>-.<<+++++++.<<----.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.++++.>>-----.--.>>.<<<.>.>>.<<+++++++.<<----.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.++++.>>>---.+++.<-.<.>------.>>.<<+++++++.<<----.++++++++++++++.--------------.+++++++.-------.>------------------.<.>>---.>>++++.<+.-.+++.++.-.<<<+++++++++++++++.>>-.++++.-----.>---.<+.<<-.>>>----.+++.<+++.<<-------.>---.<-----------------------------.++++++++++++++++++++++........>>>++++.>+.<.+.<--.>-------.<<<++++++++.----.>>>.++.>---.<<.<++++++++.>>.--..<----.>+.<+++.<<+++++.>--------.<-------------------------------.++++++++++++++++++++++........>>>>++++.<<--.---.<+++++++++.>>+.>---.<-.--.+++.<++.+++.<++.>+++++.+++.-------.<<++++++++.-.>>.>>+.<+.-.+++.++.-.<<<++++++++.>>-.++++.-----.>---.<+.<<-.>>>----.+++.<+++.<<-------.+++++.------------.+++++++.>>>>----.<<--.>>++++.----.<<<<.++.>-----------.<-------------------------------.++++++++++++++++++++++....>>>>+++++++++.<<<<----------------------..++++++++++++++++++++++....++++.>>-.---.>+++++.<.<+++++++++++.>++++++++.+++.>.<-------.>--.<<<----.>---------.<.++++++++.>>>+++++.<+.-------.<++++++.>+++++++++.---.--.++++++++.<+++++++++++++.>++++.++++.+.<<.>+++++++++++++++++.>--..<.>>++.<<<.-.>>------.+++.<++++++.>++++.<--.----.>-.<++.+++++.<.++...---------.+++++++++++++++++++++++++++++++.-------------------------------.+++++++.-------.>>>>-.<<<<.+++++++.-------.++++++++++++++.--------------.++++.>-.>.<--.>--.<---------------------.>---------------.>-----.<+++++++.<<----.++++++++++++++.--------------.+++++++.-------.++.+++++.-------.++++++++++++++.--------------.>>>++.<--.<<++++++++.----.>>-------.<.-.++++.+.>----.<<+++.>>>----------.+++.--------.>---------.<--.----.>-.<++.+++++.<<<.>>++.<<++.---------.++++++++++++++.--------------.+++++++.-----.+++++.-------.++++++++++++++++++++++++++.--------------------------.+++++++..++++++++++++++++++++.-------------------------------------------------.++++++++++++++++++++++....++++.>>>-----.>---.--..<--.>+.<+++.<<<----.>-----------------------.<.++++.>>>>+++++.++.<.>------.<<-------------.>---.>+++++.<+++++++.<<<----.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.++++.>>>-----.--.>.<<.>.>.<+++++++.<<<----.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.++++.>>>++++.+++.--------.<.>------.>.<+++++++.<<<----.++++++++++++++.--------------.++++.>>>----.---.>.<.<<+++++++++.>>++++++++.+++.>.<-------.>--.<<<<----.++++++++++++++.--------------.+++++++.-------.>>>>++++++++++.<<<<.+++++++.-------.++++++++++++++.--------------.++++.>>>>--------.<----.++++++++.+++.<.>-----------.>.<+++++++.<<<----.++++++++++++++.--------------.+++++++.-------..+++++++++++++.>>>++++++.<<<-------------.+++++++.-------.++++++++++++++.--------------.++++.>>>--.---.+++++.---------.>-.<<----.>++++.++++.----.>+.<<<-----------.<--------------------------.++++++++++++++++++++++....++++.>>>>--.<--------.>+++++.<<.>>--------.<++++++.<<<----.>++.<.>>>>++++.<+.---.+++++++..-------------.++++++.>+++++.<.--.<<<++++++++.----.>>>.>---------.--..<--.>+.<+++.<<<+++++.>--.<-------------------------------..++++++++++++++++++++++....++++.>>>+++++.>.++.+++++.-.-.<<<<----.>++.<.>>>>++++.<---.-------.<++++++++.>++++++.>+.----.<<-----------.>>------.++.+++++.-.<<<<++++++++.-.>>>+++++++.+++.--------.>-.<--.----.>-.<++.+++++.<<<.+++++.------------.+++++++..+++++.------------.+++++++..+++++.------------.>>>--.-----.>------.+++++++.<++++.<<<++++++++++++.------------.+++++++.+++++++++++.--.---------.++.---------.++++++++++++++.--------------.+++++++.-------.+++++++.>--.<-----------------------------.++++++++++++++++++++++....++++.>>>++++.+++++.++.>++.-.-.<<<<++++++++++.>++.<--------------.>>>>++++.<----------.-------.<++++++++++.>>--.<+++.>--------.----.+++++++++++.<<<<++++++++.>>>---..<<<.-.>>.>++++++.----.>--.<++.+++++.<<<.++..>--.<-------------------------------.++++++++++++++++++++++....++++.>>>>.<---.>+.++.<+++++++.>-.<<<<++++++++++.>++.>>>+++.<------.-------.<<+++++++++.>>>--------.+++.-----.<<<<------.-..+++++.------------.+++++++.>>---.-.++++.+.<<.+++++.------------.++++.>>>>----.+++++.++.+++++.-.-.<<<<++++++++.------------.+++++++.>>>++++++++.+++++.-----------.>------.++.++++++.---.<<<<.++.>-----------.<-------------------------------..++++++++++++++++++++++....>>>++++++++.---.<<<.++++++++.-------.>>>-.>-----.+++.++++.+++++.<<<<+++++++.----.>>>>-------.<----.>+++++.<<--------.>>--------.<++++++.<<<+++++..---------.>>>>++++++++++++.<<<<----------------------.++++++++++++++++++++++........++++.>>>>---------.<------.>+++++.<<.>>--------.<++++++.<<<----.>++.<.>>>--.>+++++++++.--------.----.+++.<-.+.<++++++.>>.++++++++.----.<<<<++++++++.----.>>>>-.<----.>+++++.<<------.>>--------.<++++++.<<<+++++.>--.<-------------------------------.++++++++++++++++++++++........>>>++.---.<<<.++++++++.-------.>>>-.>--.+++.++++.+++++.<<<<+++++++.----.>>>>-------.<----.>+++++.<<.>>--------.<++++++.<<<+++++..---------.>>>>++++++++++++.<<<<----------------------.++++++++++++++++++++++............++++.>>>----.++.+++++++..+++++++.<<<----.>++.<.>>>++++.-----------------.-------.<++++++++.>++.+.++++++++++.-------.<<++++++.>>.+++++++..<<<++++++++.>>+++++++++++..<<.-.>++.>>>-----.<<++++++.>++.>--.-.<<<<.++..>----------.<-------------------------------.++++++++++++++++++++++............++++.>>>>-.<+.>+++++.<++++.<<<----.>++.<.>>>>.<<+.-------.-----------.+++++++++++++.+.>-------.<+++.-------------------.>+++.>.<<<<++++++++.----.>>>------------.++.+++++++..>----.<<<<++++++++.------------.+++++++.>>>>-.<+++.>+++++.<<<<++++++++++.----------.++.>--.<-------------------------------.++++++++++++++++++++++............>>>---------.>--------.+++.<-.----.++.+++++.<<<.++++++++.----.>>>>.<-------.>+++++.<<------.>>--------.<++++++.<<<----.>>>------.>++++.<<<<.++++.>>>++++++++.>----.<<<<----.>++.+.<.++++.>>>----.----.++.+++++.<<<+++++.---------.>>>>++++++++++++.<<<<----------------------.++++++++++++++++++++++................>>>+.---.<<<.++++++++.-------.>>>-.++++++++.+++.++++.>--.<<<<+++++++.----.>>>---------------.----.++.+++++.<<<+++++..---------.>>>>++.<<<<----------------------.++++++++++++++++++++++....................++++.>>>-----.++.+++++++..+++++++.<<<----.>-.<.>>>++++.-----------------.-------.<++++++++.>++.+.++++++++++.-------.<<++++++.>>.+++++++..<<<++++++++.----.>>>-------.----.++.+++++.<<<+++++.>--------.<-------------------------------.++++++++++++++++++++++....................++++.>>>>---------.---.++++++++.----.<<<<++++++++++.>++.>>>++++.<--.-------.<.>++.+.++++++++++.-------.<--.>>--------.++++++++.<<<<------.----.>>>--.++.+++++++..>----.<<<<++++++++.------------.+++++++.>>>>-.<+++.>+++++.<<<--------.<.++.>++++++.<-------------------------------.++++++++++++++++++++++................>>>>++++++.<<<<----------------------.++++++++++++++++++++++............>>>>.<<<<----------------------.++++++++++++++++++++++............++++.>>>+++.-------------.>----------.++.<+++++++.>-.<<<<++++++++++.>++.<--------------.>>>>+++.<------.-------.<++.>++.+.++++++++++.-------.<<+++++.>>>--------.<-.>++++++++++.<<<<++++++++.----.>>>>-------.---.++++++++.----.<<<<++++++++.------------.+++++++.++++++++++.-..-----------.++.+++++.------------.++++++++++++++++.----.------------.+++++++.>>>>.----.+++.++.<---.+.>--------.<+++.<<<.++.>-------.<-------------------------------.++++++++++++++++++++++............++++.>>>>++++++.<.>+.++.<+++++++.>-.<<<<++++++++++.>++.>+++++++++++..<<------.-.>>------------.>----.>-----.++++++++.<+.+++++.-------.<<<.++.---------.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.++++.>>>+++++.---.+++++.---------.>----.<<-------.>++++.++++.----.>+.<<<<----.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.>>>----------..<<<++++++++.-.>>>>--------.<++.>+++++++.+.<<<<-------.>>>++++.>++.<.>--------.++++++.-.<<<<+++++++.++.---------.++++++++++++++.--------------.>>>>++++.<+.-------.>---.<++.++++++.<<<++++++++.-.>>>-----.>--.<<<<.++.>--.<-------------------------------.++++++++++++++++++++++............++++.>>>>.<+++.>+.++.<+++++++.>-.<<<<++++++++++.>++.<--------------.>>>>+++.<------.-------.<.>++++++++++.+++++.---.<<<++++++++.-.>++.>>++.++.-----------.>--.<++++++++.-------.<<--.>>-.++++.-----.>-----.----.+++.<++++.<<<-.>>>---.>.++++++++.---------.--.+++.<---.+++.++.>++++++.<++++++..<<.>>>-.--.+++.<-------.<<<+.+++++.------------.>>>>++.<+.-------.++++++++++.++++.------.<<<++++++++.-.>>>>----.<++++.--.+++++.>.<<<<++++++++.>>>-----.------.>----.-.<----.+++++.>+.++++++++.---------.--.+++.<---.+++.<<<-.>>>>+.--.<+++.<<<-------.++.---------.++++++++++++++.--------------.+++++++.-------.+++++++.-------.++++++++++++++.--------------.>>>--------..<<<++++++++.-.>+++++++.>>>+.++++++++.---------.--.+++.<++.+++.<<<-------.>>>++.>++++++.<++++++..<<<.>>>.+++.--------.<<<+++++++.++..>---------.<-------------------------------.++++++++++++++++++++++........>>>>++++++++.<<<<----------------------.++++++++++++++++++++++....>>>>.<<<<.>>>--.+++++++.+++++++.--------------.<<<.>>>>--.<<<<----------------------.++++++++++++++++++++++........++++.>>>++++++++.--------.>--------..<----.++++++.--.>.<<<<----.>++.<.>>>>-----.<.>+++++++++.<<<<.>>+++++++++.>---.+++++++.+++..---.+++++.-------.<--------.>--.>----..<----.++++++.--.<-----.>.+++++++.>---.<-------.>++.<<<<++++++++.+.>--.<-------------------------------.++++++++++++++++++++++........++++.>>>>.<.>+.++.<+++++++.>-.<<<<++++++++++.>++.<----------.>>>+.--------.>-..<----.++++++.--.>.<<<<+++++++++.>+.>>++.--.>+.<<+++++++++++.>>.+++++.<+++++++.-------.-.<------.>+.>------..<----.++++++.--.<<<-----.>>>------..<<<.-.>>+.>>----.+++++.<+++++++++.+.+++++.-------.<<<-------.>>>>.-----.<<<<.>>>>++++.<+.>----.++++++++.<<<<+++++++.++.+++.------------.+++++++.>>>>.<-------.>-----.----.-----.+++++.<++++++.<<<.++.>---.<-------------------------------.++++++++++++++++++++++....>>>>+++++++++++++++.<<<<----------------------..++++++++++++++++++++++....>>>>----------.<+.>----.++++++++.<---------.>.<++++++++++.+++++.----------.>--------.++++++++.<<<<++++++++.>>>-----..<<<.-.>>++++++++.>++++++++++.----.>.<<<<-------.>>>+++++++.+++.--------.<<<+++++++.++.+++.------------.++++.>>>>-----.<--.>+.++.<+++++++.>-.<<<<+++++.>.';
    $bf->setCode($code);
    $bf->execute();
} else {
    show_error(__('Access denied'));
}
?>