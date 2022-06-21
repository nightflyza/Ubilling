<?php

/**
 * AskoziaPBX calls recodrings viewer class
 */
class AskoziaMonitor extends PBXMonitor {

    /**
     * Contains default recorded calls path
     *
     * @var string
     */
    protected $voicePath = '/mnt/askozia/';

    /**
     * Contains voice recors archive path
     *
     * @var string
     */
    protected $archivePath = '/mnt/calls_archive/';

    /**
     * Contains default recorded files file extensions
     *
     * @var string
     */
    protected $callsFormat = '*.gsm';

    /**
     * Default module path
     */
    const URL_ME = '?module=askoziamonitor';

}
