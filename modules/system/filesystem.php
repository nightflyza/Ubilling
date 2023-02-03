<?php

////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * This function perform removing of files and directories
 * 
 * @param string $file
 * @param bool $recursive
 * 
 * @return bool
 */
function rcms_delete_files($file, $recursive = false) {
    while (!IGNORE_LOCK_FILES && is_file($file . '.lock')) {
        //Wait for lock to release
    }
    if ($recursive && is_dir($file)) {
        $els = rcms_scandir($file, '', '', true);
        foreach ($els as $el) {
            if ($el != '.' && $el != '..') {
                rcms_delete_files($file . '/' . $el, true);
            }
        }
    }
    if (is_dir($file)) {
        return rmdir($file);
    } else {
        return unlink($file);
    }
}

/**
 * This function perform renaming of file
 * 
 * @param string $oldfile
 * @param string $newfile
 * 
 * @return boolean
 */
function rcms_rename_file($oldfile, $newfile) {
    rename($oldfile, $newfile);
    return true;
}

/**
 * This function perform copying of file
 * 
 * @param string $oldfile
 * @param string $newfile
 * 
 * @return boolean
 */
function rcms_copy_file($oldfile, $newfile) {
    copy($oldfile, $newfile);
    return true;
}

/**
 * This function perform creating of directory
 * 
 * @param string $dir
 * 
 * @return boolean
 */
function rcms_mkdir($dir) {
    if (!is_dir($dir)) {
        if (!is_dir(dirname($dir)))
            rcms_mkdir(dirname($dir));
    }
    return @mkdir($dir, 0777);
}

/**
 * Parses standard INI-file structure and returns this as key=>value array
 * 
 * @param string $filename Existing file name
 * @param bool $blocks Section parsing flag
 * 
 * @return array
 */
function rcms_parse_ini_file($filename, $blocks = false) {
    $array1 = file($filename);
    $section = '';
    foreach ($array1 as $filedata) {
        $dataline = trim($filedata);
        $firstchar = substr($dataline, 0, 1);
        if ($firstchar != ';' && !empty($dataline)) {
            if ($blocks && $firstchar == '[' && substr($dataline, -1, 1) == ']') {
                $section = strtolower(substr($dataline, 1, -1));
            } else {
                $delimiter = strpos($dataline, '=');
                if ($delimiter > 0) {
                    preg_match("/^[\s]*(.*?)[\s]*[=][\s]*(\"|)(.*?)(\"|)[\s]*$/", $dataline, $matches);
                    $key = $matches[1];
                    $value = $matches[3];

                    if ($blocks) {
                        if (!empty($section)) {
                            $array2[$section][$key] = stripcslashes($value);
                        }
                    } else {
                        $array2[$key] = stripcslashes($value);
                    }
                } else {
                    if ($blocks) {
                        if (!empty($section)) {
                            $array2[$section][trim($dataline)] = '';
                        }
                    } else {
                        $array2[trim($dataline)] = '';
                    }
                }
            }
        }
    }
    return (!empty($array2)) ? $array2 : false;
}

/**
 * Changes access righrs for file or directory
 * 
 * @param string $file
 * @param string $val
 * @param bool $rec
 * 
 * @return bool
 */
function rcms_chmod($file, $val, $rec = false) {
    $res = @chmod(realpath($file), octdec($val));
    if (is_dir($file) && $rec) {
        $els = rcms_scandir($file);
        foreach ($els as $el) {
            $res = $res && rcms_chmod($file . '/' . $el, $val, true);
        }
    }
    return $res;
}

/**
 * This function is php5 file_put_contents copy
 * 
 * @param string $file
 * @param string $text
 * @param string $mode
 * 
 * @return boolean
 */
function file_write_contents($file, $text, $mode = 'w+') {
    if (!is_dir(dirname($file))) {
        trigger_error('Directory not found: ' . dirname($file));
        return false;
    }
    while (is_file($file . '.lock') && !@IGNORE_LOCK_FILES) {
        //Wait for lock to release
    }
    $fp = fopen($file . '.lock', 'w+');
    fwrite($fp, 'lock');
    fclose($fp);
    if ($fp = fopen($file, $mode)) {
        if (!empty($text) && !fwrite($fp, $text))
            return false;
        fclose($fp);
    } else
        return false;
    rcms_delete_files($file . '.lock');
    return true;
}

/**
 * Writes gzipped file contents
 * 
 * @param string $file
 * @param string $text
 * @param string $mode
 * 
 * @return boolean
 */
function gzfile_write_contents($file, $text, $mode = 'w+') {
    while (is_file($file . '.lock') && !@IGNORE_LOCK_FILES) {
        //Wait for lock to release
    }
    $fp = fopen($file . '.lock', 'w+');
    fwrite($fp, 'lock');
    fclose($fp);
    if ($fp = gzopen($file, $mode)) {
        if (!empty($text) && !gzwrite($fp, $text))
            return false;
        gzclose($fp);
    } else
        return false;
    rcms_delete_files($file . '.lock');
    return true;
}

if (!function_exists('file_get_contents')) {

    /**
     * This function is created for compatibility
     * 
     * @param string $file
     * 
     * @return boolean
     */
    function file_get_contents($file) {
        if (!$file = file($file))
            return false;
        if (!$file = implode('', $file))
            return false;
        return $file;
    }

}

/**
 * Returns gzipped file contents
 * 
 * @param string $file
 * 
 * @return boolean/string
 */
function gzfile_get_contents($file) {
    if (!$file = gzfile($file))
        return false;
    if (!$file = implode('', $file))
        return false;
    return $file;
}

/**
 * Function to create ini files
 * 
 * @param string $data
 * @param string $filename
 * @param string $process_sections
 * 
 * @return bool
 */
function write_ini_file($data, $filename, $process_sections = false) {
    $ini = '';
    if (!$process_sections) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $ini .= $key . ' = "' . str_replace('"', '&quot;', $value) . "\"\n";
            }
        }
    } else {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $ini .= '[' . $key . ']' . "\n";
                foreach ($value as $ekey => $evalue) {
                    $ini .= $ekey . ' = "' . str_replace('"', '&quot;', $evalue) . "\"\n";
                }
            }
        }
    }
    return file_write_contents($filename, $ini);
}

/**
 * Advanced php5 scandir analog wit some filters
 * 
 * @param string $directory Directory to scan
 * @param string $exp  Filter expression - like *.ini or *.dat
 * @param string $type Filter type - all or dir
 * @param bool $do_not_filter
 * 
 * @return array
 */
function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
    $dir = $ndir = array();
    if (!empty($exp)) {
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
    }
    if (!empty($type) && $type !== 'all') {
        $func = 'is_' . $type;
    }
    if (is_dir($directory)) {
        $fh = opendir($directory);
        while (false !== ($filename = readdir($fh))) {
            if (substr($filename, 0, 1) != '.' || $do_not_filter) {
                if ((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))) {
                    $dir[] = $filename;
                }
            }
        }
        closedir($fh);
        natsort($dir);
    }
    return $dir;
}

/**
 * Returns last file name in directory as current id
 * 
 * @param string $directory
 * @param string $ending
 * 
 * @return string
 */
function rcms_get_current_id($directory, $ending) {
    $files = rcms_scandir($directory, '*' . $ending);
    $endfile = @end($files);
    $current = substr($endfile, 0, strlen($endfile) - strlen($ending));
    $current += 1;
    return $current . $ending;
}

/**
 * Returns file permissions in readable string view
 * 
 * @param string $file
 * @param bool $if
 * 
 * @return string
 */
function get_rights_string($file, $if = false) {
    $perms = fileperms($file);
    $info = '';
    if (!$if) {
        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }
    }


    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

/**
 * Returns file permissions
 * 
 * @param string $file
 * 
 * @return string
 */
function get_rights($file) {
    return substr(sprintf('%o', fileperms($file)), -4);
}

/**
 * Returns file permissions
 * 
 * @param string $mode
 * 
 * @return int
 */
function convert_rights_string($mode) {
    $mode = str_pad($mode, 9, '-');
    $trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
    $mode = strtr($mode, $trans);
    $newmode = '0';
    $newmode .= $mode[0] + $mode[1] + $mode[2];
    $newmode .= $mode[3] + $mode[4] + $mode[5];
    $newmode .= $mode[6] + $mode[7] + $mode[8];
    return intval($newmode, 8);
}

?>